<?php

namespace ryzerbe\core\form\types\rank;

use DateInterval;
use DateTime;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\RyZerBE;
use function is_bool;

class RankPlayerForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $rank = $extraData["rank"];
        if(!$rank instanceof Rank) return;
        $form = new CustomForm(function(Player $player, $data) use ($rank): void{
            if($data === null) return;
            $playerName = $data["player"] ?? "DEINE MUDDA";
            $days = $data["days"] ?? 0;
            $months = $data["months"] ?? 0;
            $hours = $data["hours"] ?? 0;

            $duration = new DateTime();
            if($months > 0) $duration->add(new DateInterval("P" . $months . "M"));
            if($days > 0) $duration->add(new DateInterval("P" . $days . "D"));
            if($hours > 0) $duration->add(new DateInterval("PT" . $hours . "H"));
            if($days === 0 && $months === 0 && $hours === 0) $duration = true;

            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($playerName);
            $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($player);
            if($rbePlayer === null) return;
            if(($rbePlayer->getRank()->getJoinPower() <= $rank->getJoinPower()) && !$player->hasPermission("ryzer.admin")) {
                $player->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Du kannst keine höheren Ränge verteilen!");
                return;
            }
            if($ryzerPlayer === null){
                RankManager::getInstance()->setRank($playerName, $rank, $duration);
                return;
            }
            $ryzerPlayer->setRank($rank, true, true, true, $duration);
            $player->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Der Spieler " . TextFormat::GOLD . $ryzerPlayer->getPlayer()->getName() . TextFormat::GRAY . " hat den Rang " . $rank->getColor() . $rank->getRankName() . TextFormat::RESET . TextFormat::GREEN . " erhalten.");
            if(!is_bool($duration)) {
                $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Rang läuft am ".TextFormat::YELLOW.$duration->format("d.m.Y H:i").TextFormat::GRAY." aus!");
            }
        });
        $form->addInput("Months, Days, Hours = 0 -> Permanent\n\nName of the player", "Chillihero", "", "player");
        $form->addSlider("Months", 0, 12, -1, -1, "months");
        $form->addSlider("Days", 0, 30, -1, -1, "days");
        $form->addSlider("Hours", 0, 24, -1, -1, "hours");
        $form->sendToPlayer($player);
    }
}