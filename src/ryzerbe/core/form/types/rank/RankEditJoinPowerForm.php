<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\Form;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\RyZerBE;

class RankEditJoinPowerForm extends Form {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function onOpen(Player $player, array $extraData = []): void{
        $rank = $extraData["rank"];
        if(!$rank instanceof Rank) return;
        $form = new CustomForm(function(Player $player, $data) use ($rank): void{
            if($data === null) return;

            $joinPower = $data["join_power"] ?? 0;
            $rank->setJoinPower($joinPower, true);
            $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Rang ".$rank->getColor().$rank->getRankName().TextFormat::GRAY." hat nun die JoinPower ".TextFormat::GOLD.$joinPower);
        });
        $form->addInput("Tippe die JoinPower ein", "", (string)$rank->getJoinPower(), "join_power");
        $form->sendToPlayer($player);
    }
}