<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\Form;

class RankMainForm extends Form {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function onOpen(Player $player, array $extraData = []): void{
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            switch($data) {
                case "create":
                    RankCreateForm::onOpen($player);
                    break;
                case "ranks":
                    RankOverviewForm::onOpen($player);
                    break;
            }
        });
        $form->setTitle(TextFormat::RED.TextFormat::BOLD."Ranks");
        $form->addButton(TextFormat::GREEN."Create Rank", 1, "https://media.discordapp.net/attachments/602115215307309066/907559017243631636/218648.png?width=663&height=702", "create");
        $form->addButton(TextFormat::RED."Ranks", 1, "https://media.discordapp.net/attachments/602115215307309066/907558166605230080/1805999.png?width=410&height=410", "ranks");
    }
}