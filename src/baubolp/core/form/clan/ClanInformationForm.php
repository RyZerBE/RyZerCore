<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\util\Clan;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanInformationForm {

    /**
     * @param Player $player
     * @param array|null $extraData
     */
    public static function open(Player $player, ?array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan join $data");
        });

        if($extraData === null) {
            $form->setTitle(TextFormat::RED.TextFormat::BOLD."CLAN NOT FOUND!");
            $form->setContent(LanguageProvider::getMessageContainer("clan-not-found", $player->getName()));
            $form->sendToPlayer($player);
            return;
        }

        $state = match ((int)$extraData["status"]) {
            Clan::CLOSE => TextFormat::DARK_RED."CLOSE",
            Clan::INVITE => TextFormat::AQUA."ONLY INVITE",
            Clan::OPEN => TextFormat::GREEN."OPEN",
            default => "???",
        };
        $information = TextFormat::GOLD.TextFormat::BOLD."ClanTag: ".TextFormat::RESET.TextFormat::YELLOW.$extraData["clan_tag"]."\n";
        $information .= TextFormat::GOLD.TextFormat::BOLD."Owner: ".TextFormat::RESET.TextFormat::RED.$extraData["clan_owner"]."\n";
        $information .= TextFormat::GOLD.TextFormat::BOLD."Created: ".TextFormat::RESET.TextFormat::YELLOW.$extraData["created"]."\n";
        $information .= TextFormat::GOLD.TextFormat::BOLD."Info: ".TextFormat::RESET.TextFormat::YELLOW.$extraData["message"]."\n";
        $information .= TextFormat::GOLD.TextFormat::BOLD."Elo: ".TextFormat::RESET.TextFormat::YELLOW.$extraData["elo"]."\n";
        $information .= TextFormat::GOLD.TextFormat::BOLD."State: ".TextFormat::RESET.$state."\n";
        $information .= TextFormat::GOLD.TextFormat::BOLD."Members: \n".TextFormat::RESET;
        foreach($extraData["players"] as $clanMemberName) {
            $information .= "\n".TextFormat::DARK_GRAY."- ".TextFormat::AQUA.$clanMemberName;
        }
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD.$extraData["clanName"]);
        $form->setContent($information);
        if($extraData["status"] == Clan::OPEN)
        $form->addButton(TextFormat::GREEN."Join Clan", 0, "textures/ui/confirm.png", $extraData["clanName"]);
        $form->sendToPlayer($player);
    }
}