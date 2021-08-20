<?php


namespace baubolp\core\listener;


use BauboLP\BW\API\GameAPI;
use baubolp\core\player\LoginPlayerData;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\ChatLogProvider;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;

class DataPacketReceiveListener implements Listener
{

    /** @var bool */
    private bool $cancel_send = true; //INV-CRASHES FIX

    public function packetReceive(DataPacketReceiveEvent $event)
    {
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $allowedProtocols = [];
        if($player instanceof Player && $packet instanceof LoginPacket) {
            RyzerPlayerProvider::$loginData[$packet->username] = new LoginPlayerData($packet);
            if(in_array($packet->protocol, $allowedProtocols)) #1.16.100 Support
            $packet->protocol = ProtocolInfo::CURRENT_PROTOCOL;
        }

        if($packet instanceof ContainerClosePacket){
            $this->cancel_send = false;
            $event->getPlayer()->sendDataPacket($event->getPacket(), false, true);
            $this->cancel_send = true;
        }
    }

    public function onDataPacketSend(DataPacketSendEvent $event) : void{
        if($this->cancel_send && $event->getPacket() instanceof ContainerClosePacket){
            $event->setCancelled();
        }
    }
}