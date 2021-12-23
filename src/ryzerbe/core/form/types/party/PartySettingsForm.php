<?php

namespace ryzerbe\core\form\types\party;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\PartyProvider;
use function boolval;

class PartySettingsForm {

    public static function onOpen(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;
            switch($data){
                case "back":
                    PartyMainForm::onOpen($player);
                    break;
                case "ban":
                case "unban":
                    PartyPunishForm::onOpen($player, ($data === "ban") ? PartyPunishForm::BAN : PartyPunishForm::UNBAN);
                    break;
                case "open":
                case "close":
                    $player->getServer()->dispatchCommand($player, "p public");
                    break;
            }
        });
        $form->addButton(TextFormat::RED."Back", 0, "textures/ui/back_button_default.png", "back");
        $form->addButton(TextFormat::RED."Spieler bannen", 1, "https://media.discordapp.net/attachments/602115215307309066/907946777951502336/unknown.png?width=720&height=486", "ban");
        $form->addButton(TextFormat::GREEN."Spieler entbannen", 1, "https://media.discordapp.net/attachments/602115215307309066/907945456137555988/7191_unban_hammer.png?width=200&height=200", "unban");
        if($extraData["role"] === PartyProvider::PARTY_ROLE_LEADER){
            if(boolval($extraData["open"]) === true) $form->addButton(TextFormat::RED."Party schließen", 1, "https://media.discordapp.net/attachments/412217468287713282/880899284529201162/clan_role.png?width=402&height=402", "close");
            else $form->addButton(TextFormat::GREEN."Party öffnen", 1, "https://media.discordapp.net/attachments/412217468287713282/880897966993457194/clan_role.png?width=402&height=402", "open");
        }
        $form->setTitle(TextFormat::LIGHT_PURPLE."Party §gSettings");
        $form->sendToPlayer($player);
    }
}