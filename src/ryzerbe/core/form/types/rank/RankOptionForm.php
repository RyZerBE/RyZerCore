<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\Form;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;

class RankOptionForm extends Form {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function onOpen(Player $player, array $extraData = []): void{
        $rank = $extraData["rank"];
        if(!$rank instanceof Rank) return;
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;

            switch($data) {
                case "options":
                    RankEditForm::onOpen($player, $extraData);
                    break;
                case "give":
                    RankPlayerForm::onOpen($player, $extraData);
                    break;
            }
        });

        $form->addButton(TextFormat::RED."Options", 1, "https://media.discordapp.net/attachments/602115215307309066/907565324549910588/1024px-Icon_tools.png?width=702&height=702", "options");
        $form->addButton(TextFormat::GREEN."Give player", 1, "https://media.discordapp.net/attachments/602115215307309066/907565933667713044/1024px-User_icon_2.png?width=702&height=702", "give");
        $form->setTitle($rank->getColor().$rank->getRankName());
        $form->sendToPlayer($player);
    }
}