<?php

namespace ryzerbe\core\listener\server;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Server;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;

class DataPacketReceiveListener implements Listener {

    public function receive(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        /** @var PMMPPlayer $player */
        $player = $event->getPlayer();

        if($packet instanceof LoginPacket) {
            RyZerPlayerProvider::$loginData[$packet->username] = new LoginPlayerData($packet);
        }

        if($packet instanceof ContainerClosePacket){
            $player->container_packet_cancel = false;
            $event->getPlayer()->sendDataPacket($event->getPacket(), false, true);
            $player->container_packet_cancel = true;
        }
        if($packet instanceof EmotePacket){
            $emoteId = $packet->getEmoteId();
            Server::getInstance()->broadcastPacket($event->getPlayer()->getViewers(), EmotePacket::create($event->getPlayer()->getId(), $emoteId, 1 << 0));
        }
    }

    public function onDataPacketSend(DataPacketSendEvent $event) : void{
        /** @var PMMPPlayer $player */
        $player = $event->getPlayer();
        if($player->container_packet_cancel && $event->getPacket() instanceof ContainerClosePacket){
            $event->setCancelled();
        }
    }
}