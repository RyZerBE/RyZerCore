<?php

namespace ryzerbe\core\form\types\party;

use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\ConfirmationForm;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\provider\PartyProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\Settings;
use function boolval;
use function count;
use function implode;
use function strval;

class PartyMainForm {
    public static function onOpen(Player $player): void{
        $playerName = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName): array{
            $party = PartyProvider::getPartyByPlayer($mysqli, $playerName);
            if($party === null) return ["requests" => PartyProvider::getRequests($mysqli, $playerName)];
            return [
                "members" => PartyProvider::getPartyMembers($mysqli, $party),
                "role" => PartyProvider::getPlayerRole($mysqli, $playerName, false),
                "open" => PartyProvider::isPartyOpen($mysqli, $party),
                "leader" => $party
            ];
        }, function(Server $server, array $partyData) use ($playerName): void{
            $player = $server->getPlayerExact($playerName);
            if($player === null) return;
            $form = new SimpleForm(function(Player $player, $data) use ($partyData): void{
                if($data === null) return;
                switch($data){
                    case "leave":
                        ConfirmationForm::onOpen($player, LanguageProvider::getMessageContainer("really-party-leave", $player), function(Player $player) use ($partyData): void{
                            if($partyData["role"] === PartyProvider::PARTY_ROLE_LEADER){
                                $player->getServer()->dispatchCommand($player, "p delete");
                            }
                            else{
                                $player->getServer()->dispatchCommand($player, "p leave");
                            }
                        });
                        break;
                    case "member_list":
                        PartyMemberForm::onOpen($player, $partyData);
                        break;
                    case "requests":
                        PartyRequestForm::onOpen($player, $partyData);
                        break;
                    case "invite":
                        PartyInvitePlayerForm::onOpen($player);
                        break;
                    case "settings":
                        PartySettingsForm::onOpen($player, $partyData);
                        break;
                }
            });
            $memberCount = count($partyData["members"] ?? []);
            if(isset($partyData["requests"])){
                if(count($partyData["members"] ?? []) < Settings::$maxPartyPlayers) $form->addButton(TextFormat::GREEN . "Invite player", 0, "textures/ui/anvil-plus.png", "invite");
                else $form->addButton(TextFormat::RED . "Party is full!", 0, "textures/ui/anvil-plus.png", "PARTY_FULL");
                $form->addButton(TextFormat::DARK_PURPLE . "Requests", 0, "textures/ui/invite_base.png", "requests");
            }
            else{
                $form->setContent(implode("\n".TextFormat::RESET, [
                    TextFormat::LIGHT_PURPLE."Leader: ".TextFormat::WHITE.$partyData["leader"] ?? "???",
                    TextFormat::LIGHT_PURPLE."Role: ".TextFormat::WHITE.PartyProvider::getRoleNameById($partyData["role"]) ?? "???",
                    TextFormat::LIGHT_PURPLE."Player count: ".TextFormat::WHITE.$memberCount."/".Settings::$maxPartyPlayers,
                    TextFormat::LIGHT_PURPLE."Open state: ".((boolval($partyData["open"]) === true) ? TextFormat::GREEN."OPENED" : TextFormat::RED."CLOSED"),
                ]));
                $form->addButton(TextFormat::DARK_AQUA . "Party Member", 0, "textures/items/book_portfolio.png", "member_list");
                switch((int)$partyData["role"]){
                    case PartyProvider::PARTY_ROLE_MODERATOR:
                        if($memberCount < Settings::$maxPartyPlayers)  {
                            $form->addButton(TextFormat::GREEN . "Invite player", 0, "textures/ui/anvil-plus.png", "invite");
                        }else {
                            $form->addButton(TextFormat::RED . "Party is full!", 0, "textures/ui/anvil-plus.png", "PARTY_FULL");
                        }
                        $form->addButton("§gSettings", 0, "textures/ui/anvil-plus.png", "settings");
                        break;
                    case PartyProvider::PARTY_ROLE_LEADER:
                        if($memberCount < Settings::$maxPartyPlayers)  {
                            $form->addButton(TextFormat::GREEN . "Invite player", 0, "textures/ui/anvil-plus.png", "invite");
                        }else {
                            $form->addButton(TextFormat::RED . "Party is full!", 0, "textures/ui/anvil-plus.png", "PARTY_FULL");
                        }
                        $form->addButton("§gSettings", 1, "https://media.discordapp.net/attachments/412217468287713282/881163563354443806/99-998662_customizable-services-gear-settings-icon-clipart.png?width=242&height=242", "settings");
                        break;
                }
                $form->addButton(TextFormat::RED . "Leave Party", 0, "textures/ui/crossout.png", "leave");
            }
            $form->setTitle(TextFormat::LIGHT_PURPLE . "Party");
            $form->sendToPlayer($player);
        });
    }
}