<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\Listener;
use ryzerbe\core\entity\Arrow;
use ryzerbe\core\player\PMMPPlayer;

class ProjectileHitEntityListener implements Listener {

    public function arrowHit(ProjectileHitEntityEvent $event){
        $entity = $event->getEntity();
        $shooter = $entity->getOwningEntity();

        if($shooter instanceof PMMPPlayer) {
            if($entity instanceof \pocketmine\entity\projectile\Arrow || $entity instanceof Arrow|| $entity instanceof Snowball || $entity instanceof Egg)
                $shooter->playSound("random.levelup", 5, 1.0, [$shooter]);
        }
    }
}