<?php

namespace ryzerbe\core\listener\server;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\player\RyZerPlayerProvider;
use function var_dump;

class DataPacketReceiveListener implements Listener {
    public function receive(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        if($packet instanceof LoginPacket) {
            RyZerPlayerProvider::$loginData[$packet->username] = new LoginPlayerData($packet);
        }
    }
}