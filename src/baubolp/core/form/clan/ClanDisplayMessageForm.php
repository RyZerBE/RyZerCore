<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanDisplayMessageForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;

            $playerName = $data["message"];

            if(!MySQLProvider::checkInsert($playerName)) {
                $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."MySQL Injections & Sonderzeichen sind nicht erlaubt!!");
                return;
            }

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan setmessage $playerName");
        });

        $form->addInput(TextFormat::RED."Your clan display info", "", $extraData["message"] ?? "", "message");
        $form->sendToPlayer($player);
    }
}