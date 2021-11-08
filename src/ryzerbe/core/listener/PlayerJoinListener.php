<?php

namespace ryzerbe\core\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use ryzerbe\core\player\RyZerPlayerProvider;

class PlayerJoinListener implements Listener {
    /**
     * @param PlayerJoinEvent $event
     */
    public function join(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        RyZerPlayerProvider::registerRyzerPlayer($player);
    }
}