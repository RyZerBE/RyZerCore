<?php

namespace ryzerbe\core\command;

use BauboLP\Cloud\Bungee\BungeeAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\RyZerBE;

class KickCommand extends Command {

    public function __construct(){
        parent::__construct("kick", "kick player from the network", "", []);
        $this->setPermission("ryzer.kick");
        $this->setPermissionMessage(RyZerBE::PREFIX."No Permissions!");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)) return;
        if(empty($args[0]) || empty($args[1])) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."/kick <Spieler> <ID>");
            return;
        }

        $banId = $args[1];
        $playerName = $args[0];

        if(in_array($playerName, BanCommand::CANNOT_BANNED)) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Scherzkeks.. Ban/Mute doch nicht den Administrator ;)");
            return;
        }

        $punishmentReason = PunishmentProvider::getPunishmentReasonById($banId);
        if($punishmentReason === null) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Diese Id existiert nicht!");
            return;
        }
        $sender->sendMessage(RyZerBE::PREFIX."Der Spieler ".TextFormat::AQUA.$playerName.TextFormat::WHITE." wurde für ".TextFormat::AQUA.$punishmentReason->getReasonName().TextFormat::WHITE." vom Netzwerk gekickt.");
        BungeeAPI::kickPlayer($playerName, RyZerBE::PREFIX.TextFormat::RED."You were kicked! ".TextFormat::YELLOW."Reason: ".TextFormat::AQUA.$punishmentReason->getReasonName());
    }
}