<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use function var_dump;

class SelectClanWarPlayersForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $players = $extraData["players"];
        $form = new CustomForm(function(Player $player, $data) use ($players): void{
            if($data === null) return;

            $fighter_1 = $players[$data["1"]] ?? "Hurensohn";
            $fighter_2 = $players[$data["2"]] ?? "Hurensohn";
            $fighter_3 = $players[$data["3"]] ?? "Hurensohn";
            $fighter_4 = $players[$data["4"]] ?? "Hurensohn";

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "queue join $fighter_1,$fighter_2,$fighter_3,$fighter_4 ELO");
        });

        $form->addDropdown("Fighter 1", $players, null, "1");
        $form->addDropdown("Fighter 2", $players, null, "2");
        $form->addDropdown("Fighter 3", $players, null, "3");
        $form->addDropdown("Fighter 4", $players, null, "4");
        $form->sendToPlayer($player);
    }#
}