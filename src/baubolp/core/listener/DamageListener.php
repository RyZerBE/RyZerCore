<?php


namespace baubolp\core\listener;


use baubolp\core\player\RyzerPlayerProvider;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;

class DamageListener implements Listener
{


    public function Damage(EntityDamageByEntityEvent $event)
    {
        $damager = $event->getDamager();
        $entity = $event->getEntity();
        if ($entity instanceof Player && $damager instanceof Player) {
            if (($obj = RyzerPlayerProvider::getRyzerPlayer($damager->getName())) != null) {
                if($obj->isMoreParticle()) {
                    // $event->setModifier($event->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
                    $pk = new AnimatePacket();
                    $pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
                    $pk->entityRuntimeId = $entity->getId();
                    $damager->getServer()->broadcastPacket([$damager], $pk);
                    //$entity->dataPacket($pk);
                }
            }
        }
    }
}