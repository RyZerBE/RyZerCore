<?php


namespace baubolp\core\listener;


use baubolp\core\entity\TNTExplosion;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;

class ExplosionPrimeListener implements Listener
{
    public function explosion(ExplosionPrimeEvent $ev)
    {
        $ev->setCancelled();

        $tnt = $ev->getEntity();
        $explosion = new TNTExplosion(Position::fromObject($tnt->add(0, $tnt->height / 2), $tnt->level), $ev->getForce(), $tnt);
        if($ev->isBlockBreaking())
            $explosion->explodeA();

        $explosion->explodeB();
    }
}