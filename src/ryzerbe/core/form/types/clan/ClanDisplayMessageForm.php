<?php

namespace ryzerbe\core\form\types\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\MySQLProvider;
use ryzerbe\core\RyZerBE;

class ClanDisplayMessageForm {
    public static function open(Player $player, array $extraData = []){
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;
            $playerName = $data["message"];
            if(!MySQLProvider::checkInsert($playerName)){
                $player->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "MySQL Injections & Sonderzeichen sind nicht erlaubt!!");
                return;
            }
            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan setmessage $playerName");
        });
        $form->addInput(TextFormat::RED . "Your clan display info", "", $extraData["message"] ?? "", "message");
        $form->sendToPlayer($player);
    }
}