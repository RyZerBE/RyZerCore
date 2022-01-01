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
use function asort;
use function count;
use function date;
use function krsort;
use function ksort;
use function strtotime;

class CWHistoryForm {
    public static function open(Player $player, array $extraData = []){
        $clanName = $extraData["clanName"];
        $playerName = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("BetterClans", function(mysqli $mysqli) use ($clanName): ?array{
            $res = $mysqli->query("SELECT * FROM CWHistory WHERE clan_1='$clanName' OR clan_2='$clanName'");
            if($res->num_rows <= 0) return null;
            $history = [];
            while($data = $res->fetch_assoc()){
                $history[strtotime($data["date"])] = $data;
            }
            return $history;
        }, function(Server $server, ?array $result) use ($playerName): void{
            $player = $server->getPlayerExact($playerName);
            if($player === null) return;
            krsort($result);
            $newResult = [];
            foreach($result as $time => $data) {
                $newResult[date("d.m.Y H:i", $time)] = $data;
            }
            $form = new SimpleForm(function(Player $player, $data) use ($newResult): void{
                if($data === null) return;
                CWHistoryDisplayForm::open($player, ["data" => $newResult[$data] ?? []]);
            });

            if(count($newResult) <= 0) {
                $form->setContent(LanguageProvider::getMessageContainer("clan-no-cw-history", $playerName));
                $form->sendToPlayer($player);
                return;
            }

            $form->setTitle(TextFormat::GOLD . "ClanWar History");
            foreach($newResult as $date => $data){
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