<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class KickClanMemberForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan kick $data");
        });
        foreach($extraData["players"] as $clanMemberName) {
            $form->addButton($clanMemberName."\n".TextFormat::RED.TextFormat::BOLD."✘ Touch to kick", -1, "", $clanMemberName);
        }
        $form->sendToPlayer($player);
    }
}