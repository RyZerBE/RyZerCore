<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\RyZerBE;

class RankPermissionForm {
    public const METHOD_ADD = "ADD";
    public const METHOD_REMOVE = "REMOVE";

    public static function onOpen(Player $player, array $extraData = []): void{
        $rank = $extraData["rank"];
        $method = $extraData["method"] ?? self::METHOD_ADD;
        if(!$rank instanceof Rank) return;
        $form = new CustomForm(function(Player $player, $data) use ($rank, $method): void{
            if($data === null) return;
            $permission = $data["permission"];
            switch($method){
                case self::METHOD_ADD:
                    $rank->addPermission($permission, true);
                    $player->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Dem Rang " . $rank->getColor() . $rank->getRankName() . TextFormat::RESET . TextFormat::GRAY . " wurde die Permission " . TextFormat::GOLD . $permission . TextFormat::GREEN . " hinzugefügt.");
                    break;
                case self::METHOD_REMOVE:
                    $rank->removePermission($permission, true);
                    $player->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Dem Rang " . $rank->getColor() . $rank->getRankName() . TextFormat::RESET . TextFormat::GRAY . " wurde die Permission " . TextFormat::GOLD . $permission . TextFormat::RED . " entfernt.");
                    break;
            }
        });
        $form->addInput(($method === self::METHOD_ADD) ? TextFormat::GREEN . "Welche Permission möchtest du hinzufügen?" : TextFormat::RED . "Welche Permission möchtest du entfernen?", "pocketmine.command.gamemode", "", "permission");
        $form->sendToPlayer($player);
    }
}