<?php


namespace baubolp\core\command;


use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMessagePacket;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BroadcastCommand extends Command
{

    public function __construct()
    {
        parent::__construct("broadcast", "send a message to all servers", "", ['br']);
        $this->setPermission("core.broadcast");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender)) return;

        if(empty($args[0])) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/broadcast <Message>");
            return;
        }

        $message = implode(" ", $args);

        $pk = new PlayerMessagePacket();
        $pk->addData("message", "\n\n\n&fRyZer&cBE &e".$message);
        $pk->addData("players", "ALL");
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
        $sender->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Deine Nachricht wurde an ALLE Server versandt.");
    }
}