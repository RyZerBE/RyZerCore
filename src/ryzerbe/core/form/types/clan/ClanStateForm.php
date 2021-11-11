<?php

namespace ryzerbe\core\form\types\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanStateForm {
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;
            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan state $data");
        });
        $form->addButton(TextFormat::GREEN . "OPEN", 1, "https://media.discordapp.net/attachments/412217468287713282/880897966993457194/clan_role.png?width=402&height=402", "open");
        $form->addButton(TextFormat::GOLD . "ONLY INVITE", 1, "https://media.discordapp.net/attachments/412217468287713282/880558165022879775/invite.png?width=402&height=402", "invite");
        $form->addButton(TextFormat::DARK_RED . "CLOSE", 1, "https://media.discordapp.net/attachments/412217468287713282/880899284529201162/clan_role.png?width=402&height=402", "close");
        $form->setTitle(TextFormat::GOLD . TextFormat::BOLD . "Clans");
        $form->sendToPlayer($player);
    }
}