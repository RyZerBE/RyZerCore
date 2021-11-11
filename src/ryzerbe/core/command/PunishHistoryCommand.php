<?php

namespace ryzerbe\core\command;

use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\punishment\PunishmentReason;
use function explode;

class PunishHistoryCommand extends Command {
    public function __construct(){
        parent::__construct("history", "view the punishment history of a player", "", ["log"]);
        $this->setPermission("ryzer.history");
        $this->setPermission(RyZerBE::PREFIX . TextFormat::RED . "No Permissions my friend ;c");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)) return;
        if(empty($args[0])){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::YELLOW . "/history <PlayerName>");
            return;
        }
        $playerName = $args[0];
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName){
            $res = $mysqli->query("SELECT * FROM punishments WHERE player='$playerName'");
            $banHistory = [];
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()) $banHistory[] = [
                    $data["created_date"],
                    TextFormat::DARK_GRAY . $data["created_by"],
                    $data["until"],
                    $data["reason"],
                    (int)$data["type"],
                    $data["id"],
                ];
            }
            return $banHistory;
        }, function(Server $server, $result) use ($sender){
            if($sender === null) return;
            if($result === false){
                $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Etwas lief schief...");
                return;
            }
            if(count($result) <= 0){
                $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Der Spieler hat eine weiße Weste.");
                return;
            }
            foreach(array_values($result) as $banData){
                $until = $banData[2];
                if(stripos($until, "unban") !== false){
                    $state = TextFormat::DARK_RED . "UNBAN" . TextFormat::GRAY . "(" . TextFormat::BLUE . (explode("#", $until)[1] ?? "???") . TextFormat::GRAY . ") " . TextFormat::AQUA . (explode("#", $until)[2] ?? TextFormat::RED . "Kein Grund angegeben.");
                    $banData[1] = null;
                }
                else{
                    $state = (PunishmentProvider::activatePunishment($until) === true) ? TextFormat::GREEN . "ACTIVE" : TextFormat::RED . "INACTIVE";
                }
                $entryId = $banData[5];
                $createdDate = $banData[0];
                $reason = $banData[3];
                $staff = $banData[1];
                if($banData[4] === PunishmentReason::BAN){
                    if($until == "0"){
                        $sender->sendMessage($state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::DARK_RED . " PERMANENT " . TextFormat::GREEN . " gebannt." . TextFormat::GOLD . " #" . $entryId);
                    }
                    else{
                        if($banData[1] === null){
                            $sender->sendMessage($state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $banData[3] . TextFormat::GRAY . " von " . $staff . TextFormat::GREEN . " gebannt." . TextFormat::GOLD . " #" . $entryId);
                        }
                        else{
                            $sender->sendMessage($state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::GRAY . " bis zum " . TextFormat::YELLOW . $until . TextFormat::GREEN . " gebannt." . TextFormat::GOLD . " #" . $entryId);
                        }
                    }
                }
                else{
                    if($until == "0"){
                        $sender->sendMessage($state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::DARK_RED . " PERMANENT " . TextFormat::GREEN . " gemutet." . TextFormat::GOLD . " #" . $entryId);
                    }
                    else{
                        if($banData[1] === null){
                            $sender->sendMessage($state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::GREEN . " gemutet." . TextFormat::GOLD . " #" . $entryId);
                        }
                        else{
                            $sender->sendMessage($state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::GRAY . " bis zum " . TextFormat::YELLOW . $until . TextFormat::GREEN . " gemutet." . TextFormat::GOLD . " #" . $entryId);
                        }
                    }
                }
            }
        });
    }
}