<?php

namespace ryzerbe\core\form\types\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class JoinLeaveQueueOptionForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;


            if($data === "join") {
                SelectClanWarPlayersForm::open($player, $extraData);
            }else {
                CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "queue leave");
            }
        });

        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Clans");
        $form->addButton(TextFormat::GREEN."Join", 0, "textures/ui/confirm.png", "join");
        $form->addButton(TextFormat::RED."Leave", 0, "textures/ui/realms_red_x.png", "leave");
        $form->sendToPlayer($player);
    }
}