<?php

namespace ryzerbe\core\form\types\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ProgressRequestForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $clanName = $extraData["clan_name"];
        $form = new SimpleForm(function(Player $player, $data) use ($clanName): void{
            if($data === null) return;


            if($data === "yes") {
                CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan accept $clanName");
            }else {
                CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan decline $clanName");
            }
        });

        $form->addButton(TextFormat::GREEN."Join ".TextFormat::GOLD.$clanName, 0, "textures/ui/confirm.png", "yes");
        $form->addButton(TextFormat::RED."Decline", 0, "textures/ui/realms_red_x.png", "no");
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Clans");
        $form->sendToPlayer($player);
    }
}