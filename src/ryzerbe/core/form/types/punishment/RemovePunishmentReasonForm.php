<?php

namespace ryzerbe\core\form\types\punishment;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\ConfirmationForm;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\punishment\PunishmentReason;

class RemovePunishmentReasonForm {
    /**
     * @param Player $player
     */
    public static function onOpen(Player $player){
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            $punishment = PunishmentProvider::getPunishmentReasonById($data);
            if($punishment === null) return;


            ConfirmationForm::onOpen($player, TextFormat::RED."Möchtest Du wirklich den Grund ".TextFormat::YELLOW.$data.TextFormat::RED." löschen?", function(Player $player) use ($punishment, $data): void{
                PunishmentProvider::removeReason($punishment, true);
                $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Grund ".TextFormat::GOLD.$punishment->getReasonName().TextFormat::GRAY." wurde ".TextFormat::RED."gelöscht");
            });
        });

        $id = 0;
        foreach(PunishmentProvider::getPunishmentReasons() as $reason) {
            if($reason->getDays() === 0 && $reason->getHours() === 0) {
                $form->addButton(TextFormat::RED.$reason->getReasonName()."\n".TextFormat::DARK_RED."PERMANENT ".TextFormat::DARK_GRAY."[".TextFormat::AQUA.(($reason->getType() === PunishmentReason::BAN) ? "Ban" : "Mute").TextFormat::DARK_GRAY."]", -1, "", "".++$id);
            }else {
                $form->addButton(TextFormat::RED.$reason->getReasonName()."\n".TextFormat::GOLD.$reason->getDays().TextFormat::RED."D ".TextFormat::GOLD.$reason->getHours().TextFormat::RED."H".TextFormat::DARK_GRAY." [".TextFormat::AQUA.(($reason->getType() === PunishmentReason::BAN) ? "Ban" : "Mute").TextFormat::DARK_GRAY."]", -1, "", "".++$id);
            }
        }
        $form->sendToPlayer($player);
    }
}