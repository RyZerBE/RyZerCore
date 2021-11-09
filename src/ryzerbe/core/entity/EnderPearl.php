<?php

namespace ryzerbe\core\entity;

use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

class EnderPearl extends Throwable

{
    public const NETWORK_ID = self::ENDER_PEARL;

    protected function onHit(ProjectileHitEvent $event) : void{
        $owner = $this->getOwningEntity();
        if($owner !== null){
            $this->level->broadcastLevelEvent($owner, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
            $this->level->addSound(new EndermanTeleportSound($owner));
            $owner->teleport($event->getRayTraceResult()->getHitVector());
            $this->level->addSound(new EndermanTeleportSound($owner));
            //$owner->attack(new EntityDamageEvent($owner, EntityDamageEvent::CAUSE_FALL, 5));
        }
    }
}