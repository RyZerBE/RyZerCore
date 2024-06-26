<?php

namespace ryzerbe\core\entity;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\Player;
use ryzerbe\core\player\PMMPPlayer;

class Arrow extends Projectile {

    // PUNCH FLOAT \\
    const BOW_I = 0.25;
    const BOW_II = 0.5;
    const BOW_III = 1.0;

    public const NETWORK_ID = self::ARROW;

    public const PICKUP_NONE = 0;
    public const PICKUP_ANY = 1;
    public const PICKUP_CREATIVE = 2;

    private const TAG_PICKUP = "pickup"; //TAG_Byte

    /** @var float  */
    public $width = 0.25;
    /** @var float  */
    public $height = 0.25;

    protected $gravity = 0.05;
    protected $drag = 0.01;

    /** @var float */
    protected $damage = 2.0;

    /** @var int */
    protected int $pickupMode = self::PICKUP_ANY;

    /** @var float */
    protected float $punchKnockback = 0.0;

    /** @var int */
    protected int $collideTicks = 0;

    public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = null, bool $critical = false){
        parent::__construct($level, $nbt, $shootingEntity);
        $this->setCritical($critical);
    }

    protected function initEntity() : void{
        parent::initEntity();

        $this->pickupMode = $this->namedtag->getByte(self::TAG_PICKUP, self::PICKUP_ANY, true);
        $this->collideTicks = $this->namedtag->getShort("life", $this->collideTicks);
    }

    public function setThrowableMotion(Vector3 $motion, float $velocity, float $inaccuracy) : bool{
        return $this->setMotion($motion->add(
            $this->random->nextFloat() * ($this->random->nextBoolean() ? 1 : -1) * 0.0075 * $inaccuracy,
            $this->random->nextFloat() * ($this->random->nextBoolean() ? 1 : -1) * 0.0075 * $inaccuracy,
            $this->random->nextFloat() * ($this->random->nextBoolean() ? 1 : -1) * 0.0075 * $inaccuracy)
            ->multiply($velocity));
    }

    public function saveNBT() : void{
        parent::saveNBT();

        $this->namedtag->setByte(self::TAG_PICKUP, $this->pickupMode, true);
        $this->namedtag->setShort("life", $this->collideTicks);
    }

    public function isCritical() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_CRITICAL);
    }

    public function setCritical(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_CRITICAL, $value);
    }

    public function getResultDamage() : int{
        $base = parent::getResultDamage();
        if($this->isCritical()){
            return ($base + mt_rand(0, (int) ($base / 2) + 1));
        }else{
            return $base;
        }
    }

    public function getPunchKnockback() : float{
        return $this->punchKnockback;
    }

    public function setPunchKnockback(float $punchKnockback) : void{
        $this->punchKnockback = $punchKnockback;
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        if($this->closed){
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if($this->y <= -30) {
        	$this->flagForDespawn();
        	return true;
		}
        if($this->blockHit !== null){
            $this->collideTicks += $tickDiff;
            if($this->collideTicks > 1200){
                $this->flagForDespawn();
                $hasUpdate = true;
            }
        }else{
            $this->collideTicks = 0;
        }

        return $hasUpdate;
    }

    protected function onHit(ProjectileHitEvent $event) : void{
        $this->setCritical(false);
        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BOW_HIT);
    }

    protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
        parent::onHitBlock($blockHit, $hitResult);
        $this->broadcastEntityEvent(ActorEventPacket::ARROW_SHAKE, 7); //7 ticks
    }

    protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
        $shooter = $entityHit->getOwningEntity();
        if($entityHit instanceof PMMPPlayer && $shooter instanceof PMMPPlayer){
            if($entityHit->getName() === $shooter->getName()) return;
        }

        $success = $this->parentOnHitEntity($entityHit, $hitResult);
		if(!$success) return;

		$horizontalSpeed = sqrt($this->motion->x ** 2 + $this->motion->z ** 2);
        if($this->punchKnockback <= 0) $this->punchKnockback = self::BOW_I;
        if($this->punchKnockback == 1) $this->punchKnockback = self::BOW_II;
        if($this->punchKnockback == 2) $this->punchKnockback = self::BOW_III;

        if($horizontalSpeed > 0){
            $multiplier = $this->punchKnockback * 0.5 / $horizontalSpeed;
            $entityHit->setMotion($entityHit->getMotion()->add($this->motion->x * $multiplier, 0.1, $this->motion->z * $multiplier));
        }
    }

    protected function parentOnHitEntity(Entity $entityHit, RayTraceResult $hitResult): bool{
        $damage = $this->getResultDamage();

        if($damage >= 0){
            if($this->getOwningEntity() === null){
                $ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            }else{
                $ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            }

            if(!$ev->isCancelled()){
                $entityHit->attack($ev);
                $this->flagForDespawn();
                return true;
            }
            if($this->isOnFire()){
                $ev = new EntityCombustByEntityEvent($this, $entityHit, 5);
                $ev->call();
                if(!$ev->isCancelled()){
                    $entityHit->setOnFire($ev->getDuration());
                }
            }
        }

        $this->flagForDespawn();
        return true;
    }

    public function getPickupMode() : int{
        return $this->pickupMode;
    }

    public function setPickupMode(int $pickupMode) : void{
        $this->pickupMode = $pickupMode;
    }

    public function onCollideWithPlayer(Player $player) : void{
        if($this->blockHit === null){
            return;
        }

        $item = ItemFactory::get(ItemIds::ARROW);

        $playerInventory = $player->getInventory();
        if($player->isSurvival() and !$playerInventory->canAddItem($item)){
            return;
        }

        $pk = new TakeItemActorPacket();
        $pk->eid = $player->getId();
        $pk->target = $this->getId();
        $this->server->broadcastPacket($this->getViewers(), $pk);

        $playerInventory->addItem(clone $item);
        $this->flagForDespawn();
    }
}