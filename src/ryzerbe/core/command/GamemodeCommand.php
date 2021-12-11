<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\RyZerBE;

class GamemodeCommand extends Command {
    public function __construct(){
        parent::__construct("gamemode", "Change your gamemode", "", ["gm"]);
        $this->setPermission("ryzer.gamemode");
        $this->setPermissionMessage(RyZerBE::PREFIX . TextFormat::RED . "Keine Chance bro! ;c");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;
        if(!$this->testPermission($sender)) return;
        if(!isset($args[0])){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::YELLOW . "/gm <0|1|2|3> (PlayerName)");
            return;
        }
        $gm = Server::getGamemodeFromString($args[0]);
        if($gm === -1){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::YELLOW . "/gm <0|1|2|3> (PlayerName)");
            return;
        }
        if($gm == Player::CREATIVE){
            if(!$sender->hasPermission("ryzer.gamemode.creative")){
                $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Denkst auch, du wärst Gott hahahahaha");
                return;
            }
        }
        if(empty($args[1])){
            $sender->setGamemode($gm);
            $sender->sendMessage(RyZerBE::PREFIX . "Deine Spielmodus wurde aktualisiert.");
            return;
        }
        if(!$sender->hasPermission("ryzer.gamemode.other")){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Du darfst nur deinen eigenen Gamemode ändern.");
            return;
        }
        $player = Server::getInstance()->getPlayer($args[1]);
        if($player === null){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Dieser Spieler ist offline.");
            return;
        }
        $player->setGamemode($gm);
        $player->sendMessage(RyZerBE::PREFIX . "Deine Spielmodus wurde aktualisiert.");
        $sender->sendMessage(RyZerBE::PREFIX . TextFormat::GREEN . "Der Spielmodus von " . TextFormat::GOLD . $player->getName() . TextFormat::GREEN . " wurde aktualisiert.");
    }
}