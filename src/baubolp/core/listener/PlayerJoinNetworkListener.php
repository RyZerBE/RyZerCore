<?php


namespace baubolp\core\listener;


use BauboLP\Cloud\Events\PlayerJoinNetworkEvent;
use baubolp\core\provider\StaffProvider;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PlayerJoinNetworkListener implements Listener
{

    public function join(PlayerJoinNetworkEvent $event) {
        $player = Server::getInstance()->getPlayerExact($event->getPlayerName());

        if($player === null) return;

        $loggedPlayers = StaffProvider::getLoggedStaff();
        if(in_array($player->getName(), $loggedPlayers)) {
            StaffProvider::sendMessageToStaffs(TextFormat::RED.TextFormat::BOLD."Team ".TextFormat::RESET.TextFormat::DARK_GRAY."| ".$player->getName().TextFormat::GRAY." ist nun ".TextFormat::GREEN."online");
        }
    }
}