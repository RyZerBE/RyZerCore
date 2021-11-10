<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\Form;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\RyZerBE;
use function array_search;
use function str_replace;

class RankDesignForm extends Form {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function onOpen(Player $player, array $extraData = []): void{
        $rank = $extraData["rank"];
        if(!$rank instanceof Rank) return;
        $form = new CustomForm(function(Player $player, $data) use ($rank): void{
            if($data === null) return;

            $nameTag = $data["nametag"];
            $chatPrefix = $data["chatprefix"];
            $color = $data["color"];

            RankManager::getInstance()->createRank($rank->getRankName(), $nameTag, $chatPrefix, $color, $rank->getJoinPower());
            $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Rang ".$rank->getColor().$rank->getRankName().TextFormat::RESET.TextFormat::GRAY." wurde ".TextFormat::GREEN."editiert.");
        });

        $colors = ["&1", "&2", "&3", "&4", "&5", "&6", "&7", "&8", "&9", "&f", "&c", "&e", "&g", "&a", "&d"];
        $form->addInput(TextFormat::RED."Nametag of rank", "", str_replace("§", "&", $rank->getNameTag()), "nametag");
        $form->addInput(TextFormat::RED."Chatprefix of rank", "", str_replace("§", "&", $rank->getChatPrefix()), "chatperfix");
        $form->addDropdown(TextFormat::RED."Color of rank", $colors, array_search($rank->getColor(), $colors), "color");
        $form->sendToPlayer($player);
    }
}