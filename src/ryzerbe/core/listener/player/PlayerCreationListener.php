<?php

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use ryzerbe\core\player\PMMPPlayer;

class PlayerCreationListener implements Listener {
    public function creation(PlayerCreationEvent $event): void{
        $event->setPlayerClass(PMMPPlayer::class);
    }
}