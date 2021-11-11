<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use ryzerbe\core\level\TNTExplosion;

class ExplosionPrimeListener implements Listener {
    public function explosion(ExplosionPrimeEvent $ev): void{
        $ev->setCancelled();

        $tnt = $ev->getEntity();
        $explosion = new TNTExplosion(Position::fromObject($tnt->add(0, $tnt->height / 2), $tnt->level), $ev->getForce(), $tnt);
        if($ev->isBlockBreaking())
            $explosion->explodeA();

        $explosion->explodeB();
    }
}