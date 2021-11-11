<?php

namespace ryzerbe\core\command;

use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\VanishProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;

class VanishCommand extends Command {
    public function __construct(){
        parent::__construct("vanish", "", "", ["v"]);
        $this->setPermission("ryzer.vanish");
        $this->setPermissionMessage(TextFormat::RED . "No Permission!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;
        if(!$this->testPermission($sender)) return;
        $ryzerPlayer = RyzerPlayerProvider::getRyzerPlayer($sender);
        $playerName = $sender->getName();
        if($ryzerPlayer === null) return;
        if(VanishProvider::isVanished($sender->getName())){
            VanishProvider::vanishPlayer($ryzerPlayer, false);
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Du bist nun wieder sichtbar!");
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName){
                $mysqli->query("DELETE FROM `vanish` WHERE player='$playerName'");
            });
        }
        else{
            VanishProvider::vanishPlayer($ryzerPlayer, true);
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName){
                $mysqli->query("INSERT INTO `vanish`(`player`) VALUES ('$playerName')");
            });
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::GREEN . "Du bist nicht mehr sichtbar!");
        }
    }
}