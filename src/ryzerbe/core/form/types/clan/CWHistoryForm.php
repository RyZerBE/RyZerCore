<?php

namespace ryzerbe\core\form\types\clan;

use DateTime;
use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\util\async\AsyncExecutor;

class CWHistoryForm {
    public static function open(Player $player, array $extraData = []){
        $clanName = $extraData["clanName"];
        $playerName = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("BetterClans", function(mysqli $mysqli) use ($clanName): ?array{
            $res = $mysqli->query("SELECT * FROM CWHistory WHERE clan_1='$clanName' OR clan_2='$clanName'");
            if($res->num_rows <= 0) return null;
            $history = [];
            while($data = $res->fetch_assoc()){
                $history[$data["date"]] = $data;
            }
            return $history;
        }, function(Server $server, ?array $result) use ($playerName): void{
            $player = $server->getPlayerExact($playerName);
            if($player === null) return;
            $form = new SimpleForm(function(Player $player, $data) use ($result): void{
                if($data === null) return;
                CWHistoryDisplayForm::open($player, ["data" => $result[$data] ?? []]);
            });
            $form->setTitle(TextFormat::GOLD . "ClanWar History");
            if($result === null){
                $form->setContent(LanguageProvider::getMessageContainer("clan-no-cw-history", $playerName));
                $form->sendToPlayer($player);
                return;
            }
            foreach($result as $date => $data){
                $dateTime = new DateTime($date);
                $now = new DateTime("now");
                $diff = $dateTime->diff($now);
                if($diff->days <= 0){
                    $seconds = $diff->s ?? 0;
                    $minutes = $diff->i ?? 0;
                    $hours = $diff->h ?? 0;
                    $strTime = "Vor ";
                    if($minutes <= 0){
                        $strTime .= $seconds . (($seconds > 1) ? "Seconds" : "Second");
                    }
                    else{
                        if($hours > 0) $strTime .= $hours . (($hours > 1) ? "Hours" : "Hour") . ", ";
                        $strTime .= $minutes . (($minutes > 1) ? "Minutes" : "Minute");
                    }
                    $form->addButton(TextFormat::BLUE . $data["clan_1"] . TextFormat::DARK_GRAY . " VS " . TextFormat::RED . $data["clan_2"] . "\n" . TextFormat::GRAY . $strTime, -1, "", $date);
                }
                else{
                    $form->addButton(TextFormat::BLUE . $data["clan_1"] . TextFormat::DARK_GRAY . " VS " . TextFormat::RED . $data["clan_2"] . "\n" . TextFormat::GRAY . $date, -1, "", $date);
                }
            }
            $form->sendToPlayer($player);
        });
    }
}