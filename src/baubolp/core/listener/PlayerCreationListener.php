<?php


namespace baubolp\core\listener;


use baubolp\core\player\CorePlayer;
use BauboLP\LobbySystem\Utils\LPlayer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;

class PlayerCreationListener implements Listener
{

    public function creation(PlayerCreationEvent $event)
    {
        $event->setPlayerClass(CorePlayer::class);
    }
}