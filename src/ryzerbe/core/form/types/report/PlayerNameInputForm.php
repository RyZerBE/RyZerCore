<?php

namespace ryzerbe\core\form\types\report;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\MySQLProvider;

class PlayerNameInputForm {
    /**
     * @param Player $player
     */
    public static function onOpen(Player $player){
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;
            $playerName = $data["player"];
            if(!MySQLProvider::checkInsert($playerName)){
                $player->sendMessage(TextFormat::RED . "HAHAHAHAHAH DU BIST SO LUSTIG.. h0nd..");
                return;
            }

            ArchiveForm::onOpen($player, $playerName);
        });
        $form->setTitle(TextFormat::BLUE . "Report");
        $form->addInput(TextFormat::RED . "Name of Player", "Chillihero", "", "player");
        $form->sendToPlayer($player);
    }
}