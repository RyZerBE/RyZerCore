<?php

namespace baubolp\core\form\clan;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;

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
        $form->sendToPlayer($player);
    }
}