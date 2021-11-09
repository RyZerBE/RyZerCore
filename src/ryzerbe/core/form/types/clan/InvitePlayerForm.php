<?php

namespace ryzerbe\core\form\types\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\MySQLProvider;
use ryzerbe\core\RyZerBE;

class InvitePlayerForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;

            $playerName = $data["player_name"];

            if(!MySQLProvider::checkInsert($playerName)) {
                $player->sendMessage(RyZerBE::PREFIX.TextFormat::RED."MySQL Injections & Sonderzeichen sind nicht erlaubt!!");
                return;
            }

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan invite $playerName");
        });

        $form->addInput(TextFormat::RED."Name of player", "", "", "player_name");
        $form->sendToPlayer($player);
    }
}