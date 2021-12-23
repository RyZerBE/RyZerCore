<?php

namespace ryzerbe\core\form\types\party;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\provider\MySQLProvider;
use ryzerbe\core\provider\PartyProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;

class PartyPunishForm {

    const BAN = 1;
    const UNBAN = 2;

    public static function onOpen(Player $player, int $type): void{
        $form = new CustomForm(function(Player $player, $data) use ($type): void{
            if($data === null) return;
            $playerName = $data["player"];
            if(!MySQLProvider::checkInsert($playerName)){
                $player->sendMessage(TextFormat::RED . "HAHAHAHAHAH DU BIST SO LUSTIG.. h0nd..");
                return;
            }
            if($type === PartyPunishForm::BAN) $player->getServer()->dispatchCommand($player, "p ban " . $playerName);
            else if($type === PartyPunishForm::UNBAN){
                $sender = $player->getName();
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(\mysqli $mysqli) use ($sender, $playerName): bool{
                    return PartyProvider::isBannedFromParty($mysqli, PartyProvider::getPartyByPlayer($mysqli, $sender), $playerName);
                }, function(Server $server, bool $isBanned) use ($player, $playerName){
                    if(!$player->isConnected()) return;
                    if($isBanned){
                        $player->getServer()->dispatchCommand($player, "p unban ".$playerName);
                    }else{
                        $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-already-unbanned", $player, ["#player" => $playerName]));
                    }
                });
            }
        });
        $form->setTitle(TextFormat::LIGHT_PURPLE . "Party");
        $form->addInput(TextFormat::DARK_PURPLE . "Name of Player to ".(($type === self::BAN) ? "ban" : "unban"), "Chillihero", "", "player");
        $form->sendToPlayer($player);
    }
}