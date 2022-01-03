<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use ryzerbe\core\entity\Arrow;
use ryzerbe\core\player\PMMPPlayer;

class ProjectileEntityLaunchListener implements Listener {

    public function launch(ProjectileLaunchEvent $event){
        $entity = $event->getEntity();
        $owner = $event->getEntity()->getOwningEntity();
        if($entity instanceof Arrow && $owner instanceof PMMPPlayer) {
            $entity->setMotion($entity->getMotion());
            return;
        }
        if($entity instanceof Egg || $entity instanceof Snowball) {
            $entity->setMotion($entity->getMotion()->multiply(1.7));
        }
    }
}