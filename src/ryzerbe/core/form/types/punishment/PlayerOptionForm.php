<?php

namespace ryzerbe\core\form\types\punishment;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\ConfirmationForm;
use ryzerbe\core\provider\MySQLProvider;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\util\punishment\PunishmentReason;

class PlayerOptionForm {
    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function onOpen(Player $player, array $extraData = []){
        $playerName = $extraData["player"];
        $form = new SimpleForm(function(Player $player, $data) use ($playerName): void{
            if($data === null) return;
            if($data === "back") {
                PlayerNameInputForm::onOpen($player);
                return;
            }

            switch($data) {
                case "punish":
                    $form = new SimpleForm(function(Player $player, $data) use ($playerName): void{
                        if($data === null) return;
                        if($data === "back") {
                            PlayerOptionForm::onOpen($player, ["player" => $playerName]);
                            return;
                        }

                        $punishment = PunishmentProvider::getPunishmentReasonById($data);
                        ConfirmationForm::onOpen($player, TextFormat::RED."Möchtest Du wirklich den Spieler ".TextFormat::GOLD.$playerName.TextFormat::RED." für den Grund ".TextFormat::YELLOW.$punishment->getReasonName().TextFormat::RED." bestrafen?", function(Player $player) use ($playerName, $data): void{
                            $player->getServer()->dispatchCommand($player, "ban $playerName $data");
                        });
                    });

                    $id = 0;
                    $form->addButton(TextFormat::RED . "Back", 0, "textures/ui/back_button_default.png", "back");
                    foreach(PunishmentProvider::getPunishmentReasons() as $reason) {
                        if($reason->getDays() === 0 && $reason->getHours() === 0) {
                            $form->addButton(TextFormat::RED.$reason->getReasonName()."\n".TextFormat::DARK_RED."PERMANENT ".TextFormat::RED."H".TextFormat::DARK_GRAY."[".TextFormat::AQUA.(($reason->getType() === PunishmentReason::BAN) ? "Ban" : "Mute"), -1, "", "".++$id);
                        }else {
                            $form->addButton(TextFormat::RED.$reason->getReasonName()."\n".TextFormat::GOLD.$reason->getDays().TextFormat::RED."D ".TextFormat::GOLD.$reason->getHours().TextFormat::RED."H".TextFormat::DARK_GRAY." [".TextFormat::AQUA.(($reason->getType() === PunishmentReason::BAN) ? "Ban" : "Mute"), -1, "", "".++$id);
                        }
                    }
                    $form->sendToPlayer($player);
                    break;
                case "unban":

                    $form = new CustomForm(function(Player $player, $data) use ($playerName): void{
                        if($data === null) return;
                        $reason = $data["reason"];
                        if(!MySQLProvider::checkInsert($reason)){
                            $player->sendMessage(TextFormat::RED . "HAHAHAHAHAH DU BIST SO LUSTIG.. h0nd..");
                            return;
                        }

                        $player->getServer()->dispatchCommand($player, "unban $playerName ban $reason");
                    });
                    $form->setTitle(TextFormat::RED . "Punishment");
                    $form->addInput(TextFormat::RED . "Reason (max. 1 word!)", "Entbannungsantrag", "", "reason");
                    $form->sendToPlayer($player);
                    break;
                case "unmute":
                    $form = new CustomForm(function(Player $player, $data) use ($playerName): void{
                        if($data === null) return;
                        $reason = $data["reason"];
                        if(!MySQLProvider::checkInsert($reason)){
                            $player->sendMessage(TextFormat::RED . "HAHAHAHAHAH DU BIST SO LUSTIG.. h0nd..");
                            return;
                        }

                        $player->getServer()->dispatchCommand($player, "unban $playerName mute $reason");
                    });
                    $form->setTitle(TextFormat::RED . "Punishment");
                    $form->addInput(TextFormat::RED . "Reason (max. 1 word!)", "Entbannungsantrag", "", "reason");
                    $form->sendToPlayer($player);
                    break;
                case "history":
                    PunishmentHistoryForm::onOpen($player, ["player" => $playerName]);
                    break;
                case "ban_entry":
                    $form = new CustomForm(function(Player $player, $data) use ($playerName): void{
                        if($data === null) return;
                        $id = $data["id"];

                        if($id === "*") {
                            $player->getServer()->dispatchCommand($player, "banentryreset $playerName");
                        }else {
                            $player->getServer()->dispatchCommand($player, "banentryreset $id");
                        }

                    });
                    $form->setTitle(TextFormat::RED . "Punishment");
                    $form->addInput(TextFormat::RED . "EntryID (use \"*\" to remove all entries)", "7552", "", "id");
                    $form->sendToPlayer($player);
                    break;
            }
        });

        $form->addButton(TextFormat::RED."Punish $playerName", 1, "https://media.discordapp.net/attachments/602115215307309066/907946777951502336/unknown.png?width=720&height=486", "punish");
        $form->addButton(TextFormat::GREEN."Unban $playerName", 1, "https://media.discordapp.net/attachments/602115215307309066/907945456137555988/7191_unban_hammer.png?width=200&height=200", "unban");
        $form->addButton(TextFormat::GREEN."Unmute $playerName", 1, "https://media.discordapp.net/attachments/602115215307309066/907945456137555988/7191_unban_hammer.png?width=200&height=200", "unmute");
        $form->addButton(TextFormat::GOLD."History of $playerName", 1, "https://media.discordapp.net/attachments/602115215307309066/907974343227752538/2132336.png?width=410&height=410", "history");
        $form->addButton(TextFormat::GREEN."Entry reset of $playerName", 1, "https://media.discordapp.net/attachments/602115215307309066/908670000242520064/entry-forbidden-no-not-prohibited-traffic-37947.png?width=205&height=205", "ban_entry");
        $form->addButton(TextFormat::RED . "Back", 0, "textures/ui/back_button_default.png", "back");

        $form->sendToPlayer($player);
    }
}