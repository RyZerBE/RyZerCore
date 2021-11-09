<?php

namespace ryzerbe\core\listener;

use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use ryzerbe\core\util\TNTExplosion;

class ExplosionPrimeListener implements Listener {
    /**
     * @param ExplosionPrimeEvent $ev
     */
    public function explosion(ExplosionPrimeEvent $ev){
        $ev->setCancelled();

        $tnt = $ev->getEntity();
        $explosion = new TNTExplosion(Position::fromObject($tnt->add(0, $tnt->height / 2), $tnt->level), $ev->getForce(), $tnt);
        if($ev->isBlockBreaking())
            $explosion->explodeA();

        $explosion->explodeB();
    }
}