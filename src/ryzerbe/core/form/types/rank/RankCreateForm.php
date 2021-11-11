<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\RyZerBE;
use function str_replace;

class RankCreateForm {
    public static function onOpen(Player $player): void{
        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;
            $rankName = $data["name"];
            $nameTag = $data["nametag"];
            $chatPrefix = $data["chatprefix"];
            $color = "&" . $data["color"] + 1;
            $joinPower = (int)$data["joinpower"];
            RankManager::getInstance()->createRank($rankName, $nameTag, $chatPrefix, $color, $joinPower);
            $player->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Der Rang " . (str_replace("&", TextFormat::ESCAPE, $color)) . $rankName . TextFormat::RESET . TextFormat::GRAY . " wurde " . TextFormat::GREEN . "erstellt.");
        });
        $backupRank = RankManager::getInstance()->getBackupRank();
        $form->addInput(TextFormat::RED . "Name of the rank", $backupRank->getRankName(), "", "name");
        $form->addInput(TextFormat::RED . "Nametag of rank", "", str_replace("§", "&", $backupRank->getNameTag()), "nametag");
        $form->addInput(TextFormat::RED . "Chatprefix of rank", "", str_replace("§", "&", $backupRank->getChatPrefix()), "chatperfix");
        $form->addDropdown(TextFormat::RED . "Color of rank", [
            "&1",
            "&2",
            "&3",
            "&4",
            "&5",
            "&6",
            "&7",
            "&8",
            "&9",
            "&f",
            "&c",
            "&e",
            "&g",
            "&a",
            "&d",
        ], null, "color");
        $form->addInput(TextFormat::RED . "JoinPower of rank", "", "0", "joinpower");
        $form->sendToPlayer($player);
    }
}