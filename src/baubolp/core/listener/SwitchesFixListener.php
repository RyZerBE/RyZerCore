<?php


namespace baubolp\core\listener;


use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\Player;

class SwitchesFixListener implements Listener
{
    /** @var array     */
    private $delay = [];

    public function entityDamage(EntityDamageByEntityEvent $event)
    {
        $player = $event->getDamager();
        if(!$player instanceof Player) {
            $event->setCancelled(false); //NO DELAY
            return;
        }

        if(!isset($this->delay[$player->getName()]))
            $this->delay[$player->getName()] = microtime(true);

        if($player->isCreative()) return;
        if($this->delay[$player->getName()] > microtime(true)) {
            $event->setCancelled();
            return;
        }

        $this->delay[$player->getName()] = microtime(true) + 0.5;
    }
}