<?php

namespace baubolp\core\form\clan;

use baubolp\core\provider\AsyncExecutor;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SearchClanForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;

            $clanName = $data["clan_name"];

            if(!MySQLProvider::checkInsert($clanName)) {
                $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."MySQL Injections & Sonderzeichen sind nicht erlaubt!!");
                return;
            }


            SearchClanForm::sendFormAfterLoad($player->getName(), $clanName);
        });

        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Clans");
        $form->addInput(TextFormat::RED."Name of clan (with UPPER and lower cases!)", "", "", "clan_name");
        $form->sendToPlayer($player);
    }

    public static function sendFormAfterLoad(string $playerName, string $clanName): void{
        AsyncExecutor::submitMySQLAsyncTask("BetterClans", function(\mysqli $mysqli) use ($clanName, $playerName): ?array{
            $res = $mysqli->query("SELECT * FROM Clans WHERE clan_name='$clanName'");
            $loadedData = [];
            $loadedData["clanName"] = $clanName;
            if($res->num_rows > 0) {
                while($data = $res->fetch_assoc()) {
                    $loadedData["clan_tag"] = $data["clan_tag"];
                    $loadedData["clan_owner"] = $data["clan_owner"];
                    $loadedData["elo"] = $data["elo"];
                    $loadedData["color"] = $data["color"];
                    $loadedData["created"] = $data["created"];
                    $loadedData["status"] = $data["status"];
                    $loadedData["message"] = $data["message"];
                }
            }else return null;


            $playerList = [];
            $res = $mysqli->query("SELECT * FROM `ClanUsers` WHERE clan_name='$clanName'");
            if($res->num_rows > 0) {
                while($data = $res->fetch_assoc()) {
                    $playerList[] = $data["playername"];
                }
            }

            $loadedData["players"] = $playerList;
            return $loadedData;
        }, function(Server $server, ?array $result) use ($playerName){
            if(($player = $server->getPlayer($playerName)) != null)
            ClanInformationForm::open($player, $result);
        });
    }
}