<?php

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use ryzerbe\core\player\PMMPPlayer;

class PlayerHeldItemListener implements Listener {

    public function onHeld(PlayerItemHeldEvent $event){
        $player = $event->getPlayer();
        if(!$player instanceof PMMPPlayer) return;
        if($player->getPvpFishingHook() === null) return;
        $player->getPvpFishingHook()->flagForDespawn();
    }
}