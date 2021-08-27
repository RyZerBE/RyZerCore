<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function str_replace;

class ClanTagColorForm {
    
    private static array $COLORS = [
        "&1", "&2", "&3", "&4", "&5", "&6", "&7", "&8", "&9",
        "&a", "&d", "&e", "&f", "&g", "&b"
    ];

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $clanName = $extraData["clanName"];
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan color $data");
        });
        foreach(self::$COLORS as $COLOR) {
            $form->addButton(str_replace("&", TextFormat::ESCAPE, $COLOR).$clanName, -1, "", $COLOR);
        }
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Clans");
        $form->sendToPlayer($player);
    }
}