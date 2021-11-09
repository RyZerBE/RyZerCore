<?php

namespace ryzerbe\core\command;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMessagePacket;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\RyZerBE;

class BroadcastCommand extends Command {

    public function __construct(){
        parent::__construct("broadcast", "broadcast a message", "", ["br"]);
        $this->setPermission("ryzer.broadcast");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)) return;

        if(empty($args[0])) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."/broadcast <Message>");
            return;
        }

        $message = implode(" ", $args);

        $pk = new PlayerMessagePacket();
        $pk->addData("message", "\n\n\n&fRyZer&cBE &e".$message);
        $pk->addData("players", "ALL");
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
        $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GREEN."Deine Nachricht wurde an ALLE Server versandt.");
    }
}