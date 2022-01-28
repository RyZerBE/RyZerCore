<?php

namespace ryzerbe\core\anticheat\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\anticheat\AntiCheatManager;

class LiveClicksCommand extends Command {

    public function __construct(){
        parent::__construct("liveclicks", "LiveClicks Command Anticheat", "", []);
        $this->setPermission("anticheat.command.liveclicks");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;
        if(!$sender->hasPermission("anticheat.command.liveclicks")) return;

        if(empty($args[0])) {
            if(isset(AntiCheatManager::$liveClickCheck[$sender->getName()])){
                unset(AntiCheatManager::$liveClickCheck[$sender->getName()]);
                $sender->sendMessage(AntiCheatManager::PREFIX."Liveclicks von ".TextFormat::GOLD.(AntiCheatManager::$liveClickCheck[$sender->getName()] ?? "???").TextFormat::RED." deaktiviert.");
                return;
            }
            $sender->sendMessage(AntiCheatManager::PREFIX.TextFormat::RED."/liveclicks <Player>");
            return;
        }

        $player = $sender->getServer()->getPlayerExact($args[0]);
        if($player === null) {
            $sender->sendMessage(AntiCheatManager::PREFIX.TextFormat::RED."Der Spieler ist nicht auf deinem GameServer!");
            return;
        }

        if(isset(AntiCheatManager::$liveClickCheck[$sender->getName()])){
            unset(AntiCheatManager::$liveClickCheck[$sender->getName()]);
            $sender->sendMessage(AntiCheatManager::PREFIX."Liveclicks von ".TextFormat::GOLD.$player->getName().TextFormat::RED." deaktiviert.");
        }else{
            AntiCheatManager::$liveClickCheck[$sender->getName()] = $player->getName();
            $sender->sendMessage(AntiCheatManager::PREFIX."Liveclicks von ".TextFormat::GOLD.$player->getName().TextFormat::RESET." sind nun in einem Popup über deiner Hotbar.");
        }
    }
}