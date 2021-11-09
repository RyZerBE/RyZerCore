<?php

namespace ryzerbe\core\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use ryzerbe\core\player\RyZerPlayerProvider;

class PlayerQuitListener implements Listener {

    public function quit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($ryzerPlayer === null) return;

        $ryzerPlayer->saveData();
    }
}