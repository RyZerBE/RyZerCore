<?php

namespace ryzerbe\core\listener;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\player\RyZerPlayerProvider;

class DataPacketReceiveListener implements Listener {
    /**
     * @param DataPacketReceiveEvent $event
     */
    public function receive(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if($packet instanceof LoginPacket) {
            RyZerPlayerProvider::$loginData[$player->getName()] = new LoginPlayerData($packet);
        }
    }
}