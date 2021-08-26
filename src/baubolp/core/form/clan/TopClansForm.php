<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TopClansForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan info $data");
        });

        $i = 0;
        foreach($extraData["top"] as $topClanName => $elo) {
            $i++;
            $form->addButton(TextFormat::RED.$i.". ".TextFormat::GOLD."$topClanName"."\n".TextFormat::YELLOW.$elo." Elo", -1, "", $topClanName);
        }
        $form->sendToPlayer($player);
    }
}