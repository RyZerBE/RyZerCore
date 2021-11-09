<?php

namespace ryzerbe\core\form\types\clan;

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
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;

            if(isset($extraData["kick"]))
            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan kick $data");
            else {
                $extraData["giveRoleName"] = $data;
                MemberRoleUpdateForm::open($player, $extraData);
            }
        });
        foreach($extraData["players"] as $clanMemberName) {
            $form->addButton(TextFormat::DARK_AQUA.$clanMemberName."\n".((isset($extraData["kick"]) === true) ? TextFormat::RED.TextFormat::BOLD."✘ Touch to kick" : ""), -1, "", $clanMemberName);
        }
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Clans");
        $form->sendToPlayer($player);
    }
}