<?php


namespace baubolp\core\command;


use baubolp\core\provider\ModerationProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

class UnbanCommand extends Command
{

    public function __construct()
    {
        parent::__construct('unban', "allow a banned player to join the network", "", ['unpunish']);
        $this->setPermission('core.unban');
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
       if(!$this->testPermission($sender)) return;

       if(empty($args[0]) || empty($args[1]) || empty($args[2])) {
           $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/unban <Player> <Type> <Reason>");
           return;
       }

       $playerName = $args[0];
       $type = $args[1];
       $reason = $args[2];
       $senderName = $sender->getName();

       if(strtolower($type) == "ban") {
           Ryzer::getAsyncConnection()->executeQuery("SELECT ban FROM PlayerModeration WHERE playername='$playerName'", "RyzerCore", function (\mysqli_result $mysqli_result) use($senderName, $reason) {
               if($mysqli_result->num_rows > 0) {
                   while ($data = $mysqli_result->fetch_assoc()) {
                       if($data['ban'] == "") {
                           MainLogger::getLogger()->info("NOT BANNED!");
                           return false;
                       }
                       MainLogger::getLogger()->info("BANNED!");
                       return true;
                   }
               }else {
                   if(($player = Server::getInstance()->getPlayerExact($senderName))) {
                        $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Spieler existiert nicht.");
                   }
                   if($senderName == "CONSOLE") {
                       MainLogger::getLogger()->info(Ryzer::PREFIX.TextFormat::RED."Der Spieler existiert nicht.");
                   }
               }
               MainLogger::getLogger()->info("NOT EXIST!");
               return null;
           }, function ($r, $e) {}, [], function (Server $server, $result, $extra_data) use ($senderName, $playerName, $reason){
                if($result == null) {
                    if(($player = Server::getInstance()->getPlayerExact($senderName))) {
                        $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :(");
                    }
                    if($senderName == "CONSOLE") {
                        MainLogger::getLogger()->info(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :(");
                    }
                }else if($result == false) {
                    if(($player = Server::getInstance()->getPlayerExact($senderName))) {
                        $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Spieler ist nicht gebannt.");
                    }
                    if($senderName == "CONSOLE") {
                        MainLogger::getLogger()->info(Ryzer::PREFIX.TextFormat::RED."Der Spieler ist nicht gebannt.");
                    }
                }else {
                    if(($player = Server::getInstance()->getPlayerExact($senderName))) {
                        $player->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Der Spieler ".TextFormat::AQUA.$playerName.TextFormat::GREEN." wurde entbannt.");
                    }
                    if($senderName == "CONSOLE") {
                        MainLogger::getLogger()->info(Ryzer::PREFIX.TextFormat::GREEN."Der Spieler ".TextFormat::AQUA.$playerName.TextFormat::GREEN." wurde entbannt.");
                    }
                    $now = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
                    $format = $now->format('Y-m-d H:i:s');
                    ModerationProvider::addUnbanLog($playerName, $reason, $format, $senderName, true);
                    ModerationProvider::unban($playerName);
                }
           });
       }elseif(strtolower($type) == "mute") {
           Ryzer::getAsyncConnection()->executeQuery("SELECT mute FROM PlayerModeration WHERE playername='$playerName'", "RyzerCore", function (\mysqli_result $mysqli_result) use($senderName) {
               if($mysqli_result->num_rows > 0) {
                   while ($data = $mysqli_result->fetch_assoc()) {
                       if($data['mute'] == "") {
                           return false;
                       }
                       return true;
                   }
               }else {
                   if(($player = Server::getInstance()->getPlayerExact($senderName))) {
                       $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Spieler existiert nicht.");
                   }
                   if($senderName == "CONSOLE") {
                       MainLogger::getLogger()->info(Ryzer::PREFIX.TextFormat::RED."Der Spieler existiert nicht.");
                   }
               }
               return null;
           }, function ($r, $e) {}, [], function (Server $server, $result, $extra_data) use ($senderName, $playerName, $reason){
               if($result == null) {
                   if(($player = Server::getInstance()->getPlayerExact($senderName))) {
                       $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :(");
                   }
                   if($senderName == "CONSOLE") {
                       MainLogger::getLogger()->info(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :(");
                   }
               }else if($result == false) {
                   if(($player = Server::getInstance()->getPlayerExact($senderName))) {
                       $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Spieler ist nicht gemutet.");
                   }
                   if($senderName == "CONSOLE") {
                       MainLogger::getLogger()->info(Ryzer::PREFIX.TextFormat::RED."Der Spieler ist nicht gemutet.");
                   }
               }else {
                   if(($player = Server::getInstance()->getPlayerExact($senderName))) {
                       $player->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Der Spieler ".TextFormat::AQUA.$playerName.TextFormat::GREEN." wurde entmutet.");
                   }
                   if($senderName == "CONSOLE") {
                       MainLogger::getLogger()->info(Ryzer::PREFIX.TextFormat::GREEN."Der Spieler ".TextFormat::AQUA.$playerName.TextFormat::GREEN." wurde entmutet.");
                   }
                   $now = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
                   $format = $now->format('Y-m-d H:i:s');
                   ModerationProvider::addUnbanLog($playerName, $reason, $format, $senderName, false);
                   ModerationProvider::unmute($playerName);
               }
           });
       }else {
           $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Type muss mit 'Ban' oder 'Mute' gleichen!");
       }
    }
}