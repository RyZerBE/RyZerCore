<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\rank\Rank;
use function implode;

class RankEditForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $rank = $extraData["rank"];
        if(!$rank instanceof Rank) return;
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;
            switch($data){
                case "design":
                    RankDesignForm::onOpen($player, $extraData);
                    break;
                case "join_power":
                    RankEditJoinPowerForm::onOpen($player, $extraData);
                    break;
                case "add_permission":
                    $extraData["method"] = RankPermissionForm::METHOD_ADD;
                    RankPermissionForm::onOpen($player, $extraData);
                    break;
                case "remove_permission":
                    $extraData["method"] = RankPermissionForm::METHOD_REMOVE;
                    RankPermissionForm::onOpen($player, $extraData);
                    break;
            }
        });
        $form->setContent(implode("\n", $rank->getPermissions()));
        $form->addButton(TextFormat::LIGHT_PURPLE . "Design", 1, "https://media.discordapp.net/attachments/602115215307309066/907567439917744139/1454971.png?width=410&height=410", "design");
        $form->addButton(TextFormat::AQUA . "JoinPower", 1, "https://media.discordapp.net/attachments/602115215307309066/907571028790755348/1f4aa-1f3fc.png?width=410&height=410", "join_power");
        $form->addButton(TextFormat::GREEN . "Add Permission", 1, "https://media.discordapp.net/attachments/602115215307309066/907567888418869248/2172839.png?width=410&height=410", "add_permission");
        $form->addButton(TextFormat::RED . "Remove Permission", 1, "", "remove_permission");
        $form->setTitle(TextFormat::GREEN . "Edit " . $rank->getColor() . $rank->getRankName());
        $form->sendToPlayer($player);
    }
}