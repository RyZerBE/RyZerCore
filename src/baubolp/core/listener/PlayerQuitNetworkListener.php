<?php


namespace baubolp\core\listener;


use BauboLP\Cloud\Events\PlayerQuitNetworkEvent;
use baubolp\core\provider\StaffProvider;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

class PlayerQuitNetworkListener implements Listener
{

    public function quit(PlayerQuitNetworkEvent $event) {

        $loggedPlayers = StaffProvider::getLoggedStaff();
        if(in_array($event->getPlayerName(), $loggedPlayers)) {
            StaffProvider::sendMessageToStaffs(TextFormat::RED.TextFormat::BOLD."Team ".TextFormat::RESET.TextFormat::DARK_GRAY."| ".$event->getPlayerName().TextFormat::GRAY." ist nun ".TextFormat::RED."offline");
        }
    }
}