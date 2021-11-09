<?php

namespace ryzerbe\core\form\types\clan;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanRequestsForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            ProgressRequestForm::open($player, ["clan_name" => $data]);
        });
        foreach($extraData["requests"] as $request) {
            $form->addButton($request, -1, "", $request);
        }
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Clans");
        $form->sendToPlayer($player);
    }
}