<?php

namespace ryzerbe\core\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use ryzerbe\core\player\PMMPPlayer;

class PlayerCreationListener implements Listener {

    /**
     * @param PlayerCreationEvent $event
     */
    public function creation(PlayerCreationEvent $event){
        $event->setPlayerClass(PMMPPlayer::class);
    }
}