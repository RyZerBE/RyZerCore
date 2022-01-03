<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\RyZerBE;
use function in_array;
use function strlen;

class RankPermissionForm {

    public const METHOD_ADD = "ADD";
    public const METHOD_REMOVE = "REMOVE";

    public static function onOpen(Player $player, array $extraData = []): void{
        $rank = $extraData["rank"];
        $method = $extraData["method"] ?? self::METHOD_ADD;
        if(!$rank instanceof Rank) return;
        $listPermission = [];
        if(self::METHOD_ADD === $method) {
            foreach(PermissionManager::getInstance()->getPermissions() as $permission){
                if(in_array($permission->getName(), $rank->getPermissions())) continue;
                $listPermission[] = $permission->getName();
            }
        }else {
            foreach($rank->getPermissions() as $permission) $listPermission[] = $permission;
        }
        $form = new CustomForm(function(Player $player, $data) use ($rank, $method, $listPermission): void{
            if($data === null) return;
            $permission = $data["permission"];
            if(strlen($permission) === 0) {
                $permission = $listPermission[$data["permission_dropdown"]];
            }
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
            RankPermissionForm::onOpen($player, ["method" => $method, "rank" => $rank]);
        });

        $form->addDropdown("Wähle eine Permission zum ". (($method === self::METHOD_ADD) ? "hinzufügen" : "entfernen")."aus", $listPermission, null, "permission_dropdown");
        $form->addInput(($method === self::METHOD_ADD) ? TextFormat::GREEN . "Welche Permission möchtest du hinzufügen?" : TextFormat::RED . "Welche Permission möchtest du entfernen?", "pocketmine.command.gamemode", "", "permission");
        $form->sendToPlayer($player);
    }
}