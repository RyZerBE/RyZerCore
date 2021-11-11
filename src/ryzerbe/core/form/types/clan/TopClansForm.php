<?php

namespace ryzerbe\core\form\types\clan;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TopClansForm {
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;
            SearchClanForm::sendFormAfterLoad($player->getName(), $data);
        });
        $i = 0;
        foreach($extraData["top"] as $topClanName => $elo){
            $i++;
            $form->addButton(TextFormat::RED . $i . ". " . TextFormat::GOLD . "$topClanName" . "\n" . TextFormat::YELLOW . $elo . " Elo", -1, "", $topClanName);
        }
        $form->setTitle(TextFormat::GOLD . TextFormat::BOLD . "Clans");
        $form->sendToPlayer($player);
    }
}