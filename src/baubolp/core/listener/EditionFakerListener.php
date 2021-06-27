<?php


namespace baubolp\core\listener;


use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerDisconnectPacket;
use baubolp\core\listener\own\EditionFakerEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;

class EditionFakerListener implements Listener
{

    /**
     * @param \pocketmine\event\server\DataPacketReceiveEvent $event
     */
    public function receivePacket(DataPacketReceiveEvent $event)
    {
        $player = $event->getPlayer();
        $pk = $event->getPacket();
        if($pk instanceof LoginPacket) {
            $input = 0;
            if (isset($pk->clientData["DefaultInputMode"])) $input = $pk->clientData["DefaultInputMode"];
            $os = 0;
            if (isset($pk->clientData["DeviceOS"])) $os = $pk->clientData["DeviceOS"];
            $event = new EditionFakerEvent($player, $input, $os);
            $event->call();
        }
    }

    /**
     * @param \baubolp\core\listener\own\EditionFakerEvent $event
     */
    public function editionFaker(EditionFakerEvent $event)
    {
        if($event->isWhitelisted()) return;

        if($event->hasFaker()) {
            $pk = new PlayerDisconnectPacket();
            $pk->addData("playerName", $event->getPlayerName());
            $pk->addData("message", "&cYou were kicked! Please deactivate your modification!\n&eModification: &cEditionFaker");
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
        }
    }
}