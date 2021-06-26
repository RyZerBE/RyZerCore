<?php


namespace baubolp\core\listener;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\GameTimeProvider;
use baubolp\core\provider\JoinMEProvider;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class PlayerQuitListener implements Listener
{

    public function playerQuit(PlayerQuitEvent $event)
    {
        GameTimeProvider::addGameTime($event->getPlayer()->getName());
        RyzerPlayerProvider::unregisterRyzerPlayer($event->getPlayer()->getName());
        JoinMEProvider::removeJoinMe($event->getPlayer()->getName(), true);
    }
}