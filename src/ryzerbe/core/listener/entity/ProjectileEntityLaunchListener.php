<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;

class ProjectileEntityLaunchListener implements Listener {

    public function launch(ProjectileLaunchEvent $event){
        $entity = $event->getEntity();

        if($entity instanceof Egg || $entity instanceof Snowball) {
            $entity->setMotion($entity->getMotion()->multiply(1.7));
        }
    }
}