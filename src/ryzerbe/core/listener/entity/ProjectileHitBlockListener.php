<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\ProjectileHitBlockEvent;

class ProjectileHitBlockListener {

    public function hitBlock(ProjectileHitBlockEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Arrow || $entity instanceof \ryzerbe\core\entity\Arrow) $entity->flagForDespawn();
    }
}