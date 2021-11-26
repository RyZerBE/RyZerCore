<?php

namespace ryzerbe\core\form\types\party;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\SkinUtils;
use function mt_rand;

class PartyMemberForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;
            if($data === "back"){
                PartyMainForm::onOpen($player);
                return;
            }
            $extraData["member"] = $data;
            PartyMemberManageForm::onOpen($player, $extraData);
        });
        $form->addButton(TextFormat::RED . "Back", 0, "textures/ui/back_button_default.png", "back");
        foreach($extraData["members"] as $member){
            $icon = (mt_rand(1, 2) === 1) ? "textures/ui/Friend1.png" : "textures/ui/Friend2.png";
            $form->addButton(TextFormat::DARK_PURPLE . $member, 1, SkinUtils::getPlayerHeadIcon($member), $member);
        }
        $form->setTitle(TextFormat::LIGHT_PURPLE . "Party");
        $form->sendToPlayer($player);
    }
}