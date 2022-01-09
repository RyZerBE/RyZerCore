<?php

namespace ryzerbe\core\form\types\report;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\provider\ReportProvider;
use ryzerbe\core\provider\VanishProvider;
use function array_keys;
use function array_values;
use function in_array;

class ReportPlayerForm {

    const NOT_LIST_REASONS = [
        "Abschiebung",
        "AntiCheat Limit Reached"
    ];

    /**
     * @param Player $player
     */
    public static function onOpen(Player $player){
        $reasons = [];
        $playerNames = [];

        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if(!$onlinePlayer instanceof PMMPPlayer) continue;
            $ryZerPlayer = $onlinePlayer->getRyZerPlayer();
            if($ryZerPlayer === null) continue;
            if("???" === TextFormat::clean($onlinePlayer->getDisplayName())) continue; //CWBW Waiting Lobby
            if(VanishProvider::isVanished($onlinePlayer->getName())) continue;
            $playerNames[$ryZerPlayer->getName(true)] = $ryZerPlayer->getName(false);
        }

        foreach(PunishmentProvider::getPunishmentReasons() as $reason) {
            if(in_array($reason->getReasonName(), self::NOT_LIST_REASONS)) continue;
            $reasons[] = $reason->getReasonName();
        }

        $form = new CustomForm(function(Player $player, $data) use ($playerNames, $reasons): void{
            if($data === null) return;

            $badPlayer = array_keys($playerNames)[$data["bad_player"]];
            $reason = $reasons[$data["reason"]];
            $notice = $data["notice"];
            $nick = $playerNames[$badPlayer] ?? $badPlayer;

            iF($badPlayer === $player->getName() && !$player->isOp()) {
                $player->sendMessage(ReportProvider::PREFIX.LanguageProvider::getMessageContainer("cannot-report-self", $player->getName(), ['#playername' => $nick]));
                return;
            }

            ReportProvider::createReport($nick, $player->getName(), $reason, $notice, ($nick === $badPlayer) ? "No Nick" : $badPlayer);
        });
        $form->setTitle(TextFormat::BLUE."Report a player");

        $form->addDropdown(TextFormat::RED."Name of the bad boy", array_keys($playerNames), null, "bad_player");
        $form->addDropdown(TextFormat::RED."Reason", $reasons, null, "reason");
        $form->addInput(TextFormat::RED."Notice", "He has autoclicker..", "", "notice");
        $form->sendToPlayer($player);
    }
}