<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\rank\RankManager;

class RankOverviewForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;
            $rank = RankManager::getInstance()->getRank($data);
            if($rank === null) return;
            RankOptionForm::onOpen($player, ["rank" => $rank]);
        });
        $form->setTitle(TextFormat::RED . "Ranks");
        foreach(RankManager::getInstance()->getRanks() as $rank){
            $form->addButton($rank->getColor() . $rank->getRankName(), -1, "", $rank->getRankName());
        }
        $form->sendToPlayer($player);
    }
}