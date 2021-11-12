<?php

namespace ryzerbe\core\form\types\punishment;


use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PunishmentMainForm {

    /**
     * @param Player $player
     */
    public static function onOpen(Player $player){
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            switch($data) {
                case "player_options":
                    PlayerNameInputForm::onOpen($player);
                    break;
                case "add":
                    AddPunishmentReasonForm::onOpen($player);
                    break;
                case "remove":
                    RemovePunishmentReasonForm::onOpen($player);
                    break;
            }
        });

        $form->setTitle(TextFormat::RED.TextFormat::BOLD."Punishment");
        $form->addButton(TextFormat::RED."Player options", 0, "textures/ui/Friend2.png", "player_options");
        if($player->hasPermission("ryzer.ban.edit.reasons")) {
            $form->addButton(TextFormat::GREEN."Add Punishment Reason", 1, "https://media.discordapp.net/attachments/602115215307309066/908682966128025610/2419732.png?width=410&height=410", "add");
            $form->addButton(TextFormat::RED."Remove Punishment Reason", 1, "https://media.discordapp.net/attachments/602115215307309066/908683636289728542/remove_banreason.png?width=410&height=410", "remove");
        }
        $form->sendToPlayer($player);
    }
}