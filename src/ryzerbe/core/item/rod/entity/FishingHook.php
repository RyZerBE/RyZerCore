<?php

namespace ryzerbe\core\item\rod\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\utils\Random;
use ryzerbe\core\player\PMMPPlayer;

class FishingHook extends Projectile {

    public const NETWORK_ID = self::FISHING_HOOK;

    public $width = 0.25;
    public $height = 0.25;

    protected $gravity = 0.1;
    protected $drag = 0.05;

    public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = null){
        parent::__construct($level, $nbt, $shootingEntity);

        $this->setOwningEntity($shootingEntity);
        if($shootingEntity instanceof PMMPPlayer) $shootingEntity->pvpFishingHook = $this;
    }

    public function onUpdate(int $currentTick): bool{
        if($this->isFlaggedForDespawn() || !$this->isAlive()){
            return false;
        }

        $this->timings->startTiming();

        $hasUpdate = parent::onUpdate($currentTick);

        if($this->isCollidedVertically){
            $this->motion->x = 0;
            $this->motion->y += 0.01;
            $this->motion->z = 0;
            $hasUpdate = true;
        }elseif($this->isCollided && $this->keepMovement === true){
            $this->motion->x = 0;
            $this->motion->y = 0;
            $this->motion->z = 0;
            $this->keepMovement = false;
            $hasUpdate = true;
        }

        $this->timings->stopTiming();

        return $hasUpdate;
    }

    public function flagForDespawn(): void{
        parent::flagForDespawn();
        $shooter = $this->getOwningEntity();
        if($shooter instanceof PMMPPlayer) {
            if($shooter->getPvpFishingHook() !== null) $shooter->pvpFishingHook = null;
        }
    }

    public function getResultDamage(): int{
        return 1;
    }

    public function throwHook(): void{
        $entity = $this->getOwningEntity();
        if($entity === null) return;

        $location = $entity->getLocation();
        $f = $location->pitch;
        $f1 = $location->yaw;
        $f2 = cos(deg2rad(-$f1) - M_PI);
        $f3 = sin(deg2rad(-$f1) - M_PI);
        $f4 = -cos(deg2rad(-$f));
        $f5 = sin(deg2rad(-$f));
        $d0 = $location->x - $f3 * 0.3;
        $d1 = $location->y + $entity->getEyeHeight();
        $d2 = $location->z - $f2 * 0.3;
        $this->setPositionAndRotation(new Vector3($d0, $d1, $d2), $f1, $f);
        $vec3 = new Vector3(
            -$f3,
            max(-5.0, min(5.0, -($f5 / $f4))),
            -$f2
        );
        $d3 = $vec3->length();
        $random = new Random();
        $vec3 = new Vector3(
            $vec3->x * (0.6 / $d3 + 0.5 + $random->nextFloat() * 0.0045),
            $vec3->y * (0.6 / $d3 + 0.5 + $random->nextFloat() * 0.0045),
            $vec3->z * (0.6 / $d3 + 0.5 + $random->nextFloat() * 0.0045)
        );
        $this->setMotion($vec3);
        $this->setRotation(
            rad2deg(atan2($vec3->x, $vec3->z)),
            rad2deg(atan2($vec3->y, sqrt($vec3->x * $vec3->x + $vec3->z * $vec3->z)))
        );
    }

    protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void{
        if($entityHit instanceof PMMPPlayer and $this->getOwningEntity() instanceof PMMPPlayer) {
            $event = new EntityDamageByEntityEvent($this->getOwningEntity(), $entityHit, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0.0);
            $event->call();
            if(!$event->isCancelled()) {
                $entityHit->setHealth($entityHit->getHealth());
                $entityHit->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION);
                $entityHit->knockBack($this, 0.5, (float)$this->getMotion()->x, (float)$this->getMotion()->z);
            }
        }
        $this->isCollided = true;
        $this->flagForDespawn();
    }
}