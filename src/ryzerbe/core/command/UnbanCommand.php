<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\punishment\PunishmentReason;

class UnbanCommand extends Command {

    public function __construct(){
        parent::__construct("unban", "unban or unmute a player", "", []);
        $this->setPermission("ryzer.unban");
        $this->setPermissionMessage(RyZerBE::PREFIX.TextFormat::RED."No Permissions");
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
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::YELLOW."/unban <PlayerName> <Ban|Mute> <Reason>");
            return;
        }

        $playerName = $args[0];
        $type = strtolower($args[1]);
        $reason = $args[2];

        if($type != "mute" && $type != "ban") {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Bitte nutze einen validen Type. (Ban|Mute)");
            return;
        }

        PunishmentProvider::unpunishPlayer($playerName, $sender->getName(),$reason, ($type === "mute") ? PunishmentReason::MUTE : PunishmentReason::BAN);
    }
}