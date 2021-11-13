<?php

namespace ryzerbe\core\form\types\punishment;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\MySQLProvider;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\punishment\PunishmentReason;

class AddPunishmentReasonForm {
    /**
     * @param Player $player
     */
    public static function onOpen(Player $player){
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;
            $reasonName = $data["reason"];
            if(!MySQLProvider::checkInsert($reasonName)){
                $player->sendMessage(TextFormat::RED . "HAHAHAHAHAH DU BIST SO LUSTIG.. h0nd..");
                return;
            }

            $days = (int)$data["days"];
            $hours = (int)$data["hours"];
            $type = (int)$data["type"];
            PunishmentProvider::addReason(new PunishmentReason($reasonName, $days, $hours, $type), true);
            $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Grund ".TextFormat::GOLD.$reasonName.TextFormat::GRAY." wurde ".TextFormat::GREEN."hinzugefügt");
        });
        $form->setTitle(TextFormat::RED . "Punishment");
        $form->addInput(TextFormat::RED . "Reason", "Hacking", "", "reason");
        $form->addSlider("Days", 0, 30, 1, -1, "days");
        $form->addSlider("Hours", 0, 23, 1, -1, "hours");
        $form->addDropdown("Type", ["Ban", "Mute"], null, "type");
        $form->sendToPlayer($player);
    }
}