<?php

namespace ryzerbe\core\form\types;

use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\punishment\PunishmentReason;
use ryzerbe\core\util\time\TimeAPI;
use function explode;
use function implode;

class UserInfoForm {

    /**
     * @param Player $player
     * @param string $playerName
     */
    public static function onOpen(Player $player, string $playerName){
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName): ?array{
            $res = $mysqli->query("SELECT * FROM playerdata WHERE player='$playerName'");

            $data = [];
            if($res->num_rows <= 0) return null;
            if($fetchedData = $res->fetch_assoc()){
                $data = $fetchedData;
            }

            $res = $mysqli->query("SELECT accounts FROM second_accounts WHERE player='$playerName'");
            if($res->num_rows <= 0) $data["accounts"] = [TextFormat::RED."Keine registrierten Zweitaccounts!"];
            else if($fetchedData = $res->fetch_assoc()) $data["accounts"] = explode(":", $fetchedData["accounts"]);

            $res = $mysqli->query("SELECT rankname FROM playerranks WHERE player='$playerName'");
            if($res->num_rows > 0) {
                $data["rank"] = $res->fetch_assoc()["rankname"];
            }

            $res = $mysqli->query("SELECT coins FROM coins WHERE player='$playerName'");
            if($res->num_rows > 0) {
                $data["coins"] = $res->fetch_assoc()["coins"];
            }

            $res = $mysqli->query("SELECT coins FROM coins WHERE player='$playerName'");
            if($res->num_rows > 0) {
                $data["coins"] = $res->fetch_assoc()["coins"];
            }

            $res = $mysqli->query("SELECT ticks FROM gametime WHERE player='$playerName'");
            if($res->num_rows > 0) {
                $data["ticks"] = $res->fetch_assoc()["ticks"];
            }

            $res = $mysqli->query("SELECT level FROM networklevel WHERE playername='$playerName'");
            if($res->num_rows > 0) {
                $data["level"] = $res->fetch_assoc()["level"];
            }

            $res = $mysqli->query("SELECT * FROM punishments WHERE player='$playerName'");
            $banPoints = 0;
            $mutePoints = 0;
            if($res->num_rows > 0) {
                while($fetchedData = $res->fetch_assoc()) {
                    if($fetchedData["type"] == PunishmentReason::BAN) $banPoints++; else $mutePoints++;
                    if(PunishmentProvider::activatePunishment($fetchedData["until"])) {
                        $type =($fetchedData["type"] == PunishmentReason::BAN) ? "ban" : "mute";
                        $data[$type."_reason"] = $fetchedData["reason"];
                        $data[$type."_id"] = $fetchedData["id"];
                    }
                }
            }

            $data["ban_points"] = $banPoints;
            $data["mute_points"] = $mutePoints;

            return $data;
        }, function(Server $server, ?array $data) use ($player, $playerName): void{
            if(!$player->isConnected()) return;

            if($data === null) {
                $player->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Der Spieler ist nicht registriert!");
                return;
            }
            $content = [];
            $content[] = TextFormat::GOLD."Coins: ".TextFormat::WHITE.$data["coins"]." Coins";
            $content[] = TextFormat::GOLD."Gametime: ".TextFormat::WHITE.TimeAPI::convert($data["ticks"])->asString();
            $content[] = TextFormat::GOLD."Level: ".TextFormat::WHITE.$data["level"];
            $content[] = TextFormat::GOLD."Rang: ".TextFormat::WHITE.$data["rank"];
            $content[] = TextFormat::GOLD."Registerierung: ".TextFormat::WHITE.$data["first_join"];
            $content[] = TextFormat::GOLD."Status: ".TextFormat::WHITE.(($data["server"] === "?") ? TextFormat::RED."Zuletzt online am ".TextFormat::GOLD.$data["last_join"] : TextFormat::GREEN."Online auf ".TextFormat::GOLD.$data["server"]);
            $content[] = TextFormat::GOLD."Gerät: ".TextFormat::WHITE.LoginPlayerData::$deviceOSValues[$data["device_os"]];
            $content[] = TextFormat::GOLD."Mit dem Gerät verbunden: ".TextFormat::WHITE.LoginPlayerData::$inputValues[$data["device_input"]];
            $content[] = TextFormat::GOLD."Minecraft ID: ".TextFormat::WHITE.$data["minecraft_id"];
            $content[] = TextFormat::GOLD."IP Addresse: ".TextFormat::WHITE.(($player->hasPermission("ryzer.admin") === true) ? $data["ip_address"] : explode(".", $data["ip_address"])[1].".".explode(".", $data["ip_address"])[2]).".X".".X";
            $content[] = TextFormat::GOLD."Device ID: ".TextFormat::WHITE.$data["device_id"];
            $content[] = "\n";
            $content[] = TextFormat::GOLD."Spielsperre: ".((isset($data["ban_reason"]) === true) ? TextFormat::GREEN."POSITIV".TextFormat::GRAY."(".TextFormat::AQUA.$data["ban_reason"].TextFormat::GRAY.")" : TextFormat::RED."NEGATIV");
            $content[] = TextFormat::GOLD."Chatsperre: ".((isset($data["mute_reason"]) === true) ? TextFormat::GREEN."POSITIV".TextFormat::GRAY."(".TextFormat::AQUA.$data["mute_reason"].TextFormat::GRAY.")" : TextFormat::RED."NEGATIV");
            $content[] = TextFormat::GOLD."Banpunkte: ".TextFormat::WHITE.$data["ban_points"];
            $content[] = TextFormat::GOLD."Mutepunkte: ".TextFormat::WHITE.$data["mute_points"];
            $content[] = "\n";
            $content[] = TextFormat::GOLD."Verbundene Accounts: \n".TextFormat::DARK_GRAY."-> ".TextFormat::RED.implode("\n".TextFormat::DARK_GRAY."-> ".TextFormat::RED, $data["accounts"]);

            $form = new SimpleForm(function(Player $player, $data): void{
                $player->sendMessage("\n\n".RyZerBE::PREFIX.TextFormat::RED.TextFormat::BOLD."Bitte behandel diese Daten vertraulich! Stell dir vor, es wären deine Daten..\n\n");
            });

            $form->setContent(implode("\n", $content));
            $form->setTitle(TextFormat::GOLD.$playerName);
            $form->sendToPlayer($player);
        });
    }
}