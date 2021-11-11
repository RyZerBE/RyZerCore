<?php

namespace ryzerbe\core\form\types\party;

use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\Form;
use ryzerbe\core\form\types\ConfirmationForm;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\provider\PartyProvider;
use ryzerbe\core\util\async\AsyncExecutor;

class PartyMainForm extends Form {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function onOpen(Player $player, array $extraData = []): void{
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;
        });

        $playerName = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName): array{
            $party = PartyProvider::getPartyByPlayer($mysqli, $playerName);
            if($party === null) return ["requests" => PartyProvider::getRequests($mysqli, $playerName)];

            return ["members" => PartyProvider::getPartyMembers($mysqli, $party), "role" => PartyProvider::getPlayerRole($mysqli, $playerName, false), "open" => PartyProvider::isPartyOpen($mysqli, $party)];
        }, function(Server $server, array $partyData) use ($playerName): void{
            $player = $server->getPlayerExact($playerName);
            if($player === null) return;

            $form = new SimpleForm(function(Player $player, $data) use ($partyData): void{
                if($data === null) return;

                switch($data) {
                    case "leave":
                        ConfirmationForm::onOpen($player, LanguageProvider::getMessageContainer("really-party-leave", $player), function(Player $player) use ($partyData): void{
                            if($partyData["role"] === PartyProvider::PARTY_ROLE_LEADER) {
                                $player->getServer()->dispatchCommand($player, "p delete");
                            }else {
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
                }
            });
            if(isset($partyData["requests"])) {
                $form->addButton(TextFormat::GREEN."Invite player", 0, "textures/ui/anvil-plus.png", "invite");
                $form->addButton(TextFormat::DARK_PURPLE."Requests", 0, "textures/ui/invite_base.png", "requests");
            }else {
                $form->addButton(TextFormat::DARK_AQUA."Party Member", 0, "textures/items/book_portfolio.png", "member_list");
                switch((int)$partyData["role"]) {
                    case PartyProvider::PARTY_ROLE_MEMBER:
                        $form->addButton(TextFormat::RED."Leave Party", 0, "textures/ui/crossout.png", "leave");
                        break;
                    case PartyProvider::PARTY_ROLE_MODERATOR:
                        break;
                    case PartyProvider::PARTY_ROLE_LEADER:
                        break;
                }
                $form->addButton(TextFormat::RED."Leave Party", 0, "textures/ui/crossout.png", "leave");
            }
            $form->setTitle(TextFormat::LIGHT_PURPLE."Party");
            $form->sendToPlayer($player);
        });
    }
}