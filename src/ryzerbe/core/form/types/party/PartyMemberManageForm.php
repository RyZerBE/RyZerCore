<?php

namespace ryzerbe\core\form\types\party;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\ConfirmationForm;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\provider\PartyProvider;
use ryzerbe\core\RyZerBE;

class PartyMemberManageForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;
            switch($data){
                case "back":
                    PartyMainForm::onOpen($player);
                    break;
                case "role":
                    $player->sendMessage(TextFormat::RED . "Die Funktion kommt hinzu, sobald ich wieder lust hab am partysystem zu arbeiten."); //todo
                    break;
                case "kick":
                    ConfirmationForm::onOpen($player, LanguageProvider::getMessageContainer("really-party-leave", $player), function(Player $player) use ($extraData): void{
                        $player->getServer()->dispatchCommand($player, "p kick " . $extraData["member"]);
                    });
                    break;
                case "friend":
                    $player->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Das Freundesystem kommt in einem weiteren Update!");
                    break;
                case "clan":
                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan invite ". $extraData["member"]);
                    break;
            }
        });
        $form->addButton(TextFormat::RED . "Back", 0, "textures/ui/back_button_default.png", "back");
        if($extraData["role"] === PartyProvider::PARTY_ROLE_LEADER){
            $form->addButton(TextFormat::GREEN . "Role upgrade", 1, "https://media.discordapp.net/attachments/412217468287713282/880869858668052510/clan_role.png?width=224&height=224", "role");
        }
        if($extraData["role"] > PartyProvider::PARTY_ROLE_MEMBER){
            $form->addButton(TextFormat::RED . "Kick " . $extraData["member"], 0, "textures/ui/crossout.png", "kick");
        }
        $form->addButton(TextFormat::GREEN . "Als Freund hinzufügen", 0, "textures/ui/Friend1.png", "friend");
        $form->addButton(TextFormat::GOLD . "In meinen Clan einladen", 1, "https://media.discordapp.net/attachments/412217468287713282/881162752280903750/War_Leagues.png?width=144&height=144", "clan");
        $form->setTitle(TextFormat::LIGHT_PURPLE . "Party " . TextFormat::YELLOW . $extraData["member"]);
        $form->sendToPlayer($player);
    }
}