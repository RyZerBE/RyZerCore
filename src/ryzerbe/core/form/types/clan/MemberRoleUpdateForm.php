<?php

namespace ryzerbe\core\form\types\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MemberRoleUpdateForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;

            $giveRole = $extraData["giveRoleName"];
            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan role $giveRole $data");
        });

        foreach($extraData["roles"] as $roleName => $priority) {
            $form->addButton(TextFormat::GREEN.$roleName, -1, "", $roleName);
        }
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Clans");
        $form->sendToPlayer($player);
    }
}