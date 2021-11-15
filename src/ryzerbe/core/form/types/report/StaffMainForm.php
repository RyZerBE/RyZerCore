<?php

namespace ryzerbe\core\form\types\report;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class StaffMainForm {

    /**
     * @param Player $player
     */
    public static function onOpen(Player $player){
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;

            switch($data) {
                case "reports":
                    ReportsOverviewForm::onOpen($player);
                    break;
                case "archive":
                    PlayerNameInputForm::onOpen($player);
                    break;
            }
        });

        $form->setTitle(TextFormat::BLUE."Reports Panel");
        $form->addButton(TextFormat::RED."Reports", 1, "https://cdn.discordapp.com/attachments/602115215307309066/909752837339234324/exclamation-icon-15.png", "reports");
        $form->addButton(TextFormat::GREEN."Archiv", 1, "https://cdn.discordapp.com/attachments/602115215307309066/909754033865130014/1157026.png", "archive");
        $form->sendToPlayer($player);
    }
}