<?php

namespace ryzerbe\core\anticheat\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\anticheat\AntiCheatManager;
use ryzerbe\core\anticheat\type\KillAura;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\RyZerBE;

class CheckKillAuraCommand extends Command {

    public function __construct(){
        parent::__construct("checkkillaura", "", "", ["cka"]);
        $this->setPermission("ryzer.killaura.check");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if (!$this->testPermission($sender)) return;
        $killAuraModule = new KillAura();
        if(!AntiCheatManager::isCheckRegistered($killAuraModule)) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Das CheckModule \"KillAura\" ist nicht aktiviert!");
            return;
        }
        if (empty($args[0])) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."/checkkillaura <Player>");
            return;
        }

        $checkedPlayer = Server::getInstance()->getPlayer($args[0]);
        if ($checkedPlayer === null) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Der Spieler befindet sich nicht auf deinem GameServer!");
            return;
        }
        $acPlayer = AntiCheatManager::getPlayer($checkedPlayer);
        if($acPlayer === null) return;
        $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GREEN."Der Spieler wird auf KillAura gecheckt...");
        $killAuraModule->spawnBotToPlayer($acPlayer);
    }
}