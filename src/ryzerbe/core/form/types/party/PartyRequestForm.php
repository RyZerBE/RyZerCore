<?php

namespace ryzerbe\core\form\types\party;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\ConfirmationForm;
use ryzerbe\core\language\LanguageProvider;

class PartyRequestForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;
            if($data === "back"){
                PartyMainForm::onOpen($player);
                return;
            }
            ConfirmationForm::onOpen($player, LanguageProvider::getMessageContainer("party-reqeuest-really-accept", $player->getName(), ["#player" => $data]), function(Player $player) use ($data): void{
                $player->getServer()->dispatchCommand($player, "p accept $data");
            });
        });
        $form->setTitle(TextFormat::LIGHT_PURPLE . "Party");
        $form->addButton(TextFormat::RED . "Back", 0, "textures/ui/back_button_default.png", "back");
        foreach($extraData["requests"] as $request){
            $form->addButton(TextFormat::DARK_PURPLE . $request, 0, "textures/ui/invite_base.png", $request);
        }
        $form->sendToPlayer($player);
    }
}