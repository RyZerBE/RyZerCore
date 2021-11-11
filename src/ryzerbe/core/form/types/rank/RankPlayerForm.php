<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\RyZerBE;

class RankPlayerForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $rank = $extraData["rank"];
        if(!$rank instanceof Rank) return;
        $form = new CustomForm(function(Player $player, $data) use ($rank): void{
            if($data === null) return;
            $playerName = $data["player"] ?? "DEINE MUDDA";
            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($playerName);
            if($ryzerPlayer === null){
                RankManager::getInstance()->setRank($playerName, $rank);
                return;
            }
            $ryzerPlayer->setRank($rank, true, true, true);
            $player->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Der Spieler " . TextFormat::GOLD . $ryzerPlayer->getPlayer()->getName() . TextFormat::GRAY . " hat den Rang " . $rank->getColor() . $rank->getRankName() . TextFormat::RESET . TextFormat::GREEN . " erhalten.");
        });
        $form->addInput("Name of the player", "Chillihero", "", "player");
        $form->sendToPlayer($player);
    }
}