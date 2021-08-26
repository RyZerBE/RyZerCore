<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function str_replace;

class CreateClanForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;

            $clanName = str_replace(" ", "_", $data["clan_name"]);
            $clanTag = str_replace(" ", "_", $data["clan_tag"]);

            if(!MySQLProvider::checkInsert($clanName) || !MySQLProvider::checkInsert($clanTag)) {
                $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."MySQL Injections & Sonderzeichen sind nicht erlaubt!!");
                return;
            }

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan create $clanName $clanTag");
        });

        $form->addInput(TextFormat::RED."Name of your clan", "", "", "clan_name");
        $form->addInput(TextFormat::RED."Tag of your clan", "", "", "clan_tag");
        $form->sendToPlayer($player);
    }
}