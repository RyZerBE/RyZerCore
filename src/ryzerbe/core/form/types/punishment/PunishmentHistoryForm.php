<?php

namespace ryzerbe\core\form\types\punishment;

use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\punishment\PunishmentReason;
use function array_values;
use function count;
use function explode;
use function implode;
use function stripos;

class PunishmentHistoryForm {
    /**
     * @param Player $sender
     * @param array $extraData
     */
    public static function onOpen(Player $sender, array $extraData = []){
        $playerName = $extraData["player"];
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
        }, function(Server $server, $result) use ($sender, $playerName){
            if($sender === null) return;
            if(count($result) <= 0){
                $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Der Spieler hat eine weiße Weste.");
                return;
            }
            $content = [];
            $form = new SimpleForm(function(Player $player, $data) use ($playerName): void{
                if($data === null) return;

                PlayerOptionForm::onOpen($player, ["player" => $playerName]);
            });
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
                        $content[] = $state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::DARK_RED . " PERMANENT " . TextFormat::GREEN . " gebannt." . TextFormat::GOLD . " #" . $entryId;
                    }
                    else{
                        if($banData[1] === null){
                            $content[] = $state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $banData[3] . TextFormat::GRAY . " von " . $staff . TextFormat::GREEN . " gebannt." . TextFormat::GOLD . " #" . $entryId;
                        }
                        else{
                            $content[] = $state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::GRAY . " bis zum " . TextFormat::YELLOW . $until . TextFormat::GREEN . " gebannt." . TextFormat::GOLD . " #" . $entryId;
                        }
                    }
                }
                else{
                    if($until == "0"){
                        $content[] = $state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::DARK_RED . " PERMANENT " . TextFormat::GREEN . " gemutet." . TextFormat::GOLD . " #" . $entryId;
                    }
                    else{
                        if($banData[1] === null){
                            $content[]  = $state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::GREEN . " gemutet." . TextFormat::GOLD . " #" . $entryId;
                        }
                        else{
                            $content[] = $state . TextFormat::DARK_GRAY . " | " . TextFormat::GRAY . "Am " . TextFormat::YELLOW . $createdDate . TextFormat::GRAY . " für " . TextFormat::RED . $reason . TextFormat::GRAY . " von " . $staff . TextFormat::GRAY . " bis zum " . TextFormat::YELLOW . $until . TextFormat::GREEN . " gemutet." . TextFormat::GOLD . " #" . $entryId;
                        }
                    }
                }
            }
            $form->addButton(TextFormat::RED . "Back", 0, "textures/ui/back_button_default.png", "back");
            $form->setTitle(TextFormat::RED.$playerName);
            $form->setContent(implode("\n", $content));
            $form->sendToPlayer($sender);
        });
    }
}