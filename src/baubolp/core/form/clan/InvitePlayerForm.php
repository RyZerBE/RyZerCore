<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function str_replace;

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
                $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."MySQL Injections & Sonderzeichen sind nicht erlaubt!!");
                return;
            }

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan invite $playerName");
        });

        $form->addInput(TextFormat::RED."Name of player", "", "", "player_name");
        $form->sendToPlayer($player);
    }
}