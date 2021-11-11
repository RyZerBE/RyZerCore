<?php

namespace ryzerbe\core\form\types\party;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\MySQLProvider;

class PartyInvitePlayerForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;
            $playerName = $data["player"];
            if(!MySQLProvider::checkInsert($playerName)){
                $player->sendMessage(TextFormat::RED . "HAHAHAHAHAH DU BIST SO LUSTIG.. h0nd..");
                return;
            }
            $player->getServer()->dispatchCommand($player, "p invite " . $playerName);
        });
        $form->setTitle(TextFormat::LIGHT_PURPLE . "Party");
        $form->addInput(TextFormat::DARK_PURPLE . "Name of Player", "Chillihero", "", "player");
        $form->sendToPlayer($player);
    }
}