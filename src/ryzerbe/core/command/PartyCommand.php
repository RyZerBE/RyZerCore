<?php

namespace ryzerbe\core\command;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMessagePacket;
use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\party\PartyMainForm;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\provider\PartyProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\Settings;
use function count;
use function implode;
use function is_array;
use function str_replace;

class PartyCommand extends Command {
    public function __construct(){
        parent::__construct("party", "create and manage your party", "", ["p"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof PMMPPlayer) return;
        if(empty($args[0])){
            PartyMainForm::onOpen($sender);
            return;
        }
        $subCommand = $args[0];
        switch($subCommand){
            case "info":
                PartyMainForm::onOpen($sender);
                break;
            case "invite":
                if(empty($args[1])){
                    $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Syntax error: /party invite <Player>");
                    return;
                }
                $playerName = $args[1];
                $senderName = $sender->getName();
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $senderName): int{
                    $party = PartyProvider::getPartyByPlayer($mysqli, $senderName);

                    if(!PartyProvider::validPlayer($mysqli, $playerName)) return PartyProvider::PLAYER_DOESNT_EXIST;
                    if($party === null){
                        $party = $senderName;
                        PartyProvider::createParty($mysqli, $senderName);
                    }
                    $members = PartyProvider::getPartyMembers($mysqli, $party);
                    if(PartyProvider::getPartyByPlayer($mysqli, $playerName) !== null) return PartyProvider::ALREADY_IN_PARTY;
                    if(PartyProvider::hasRequest($mysqli, $party, $playerName)) return PartyProvider::ALREADY_REQUEST;
                    if(PartyProvider::getPlayerRole($mysqli, $senderName, false) < PartyProvider::PARTY_ROLE_MODERATOR) return PartyProvider::NO_PERMISSION;
                    if(count($members) >= Settings::$maxPartyPlayers) return PartyProvider::PARTY_FULL;
                    PartyProvider::addRequest($mysqli, $party, $playerName);
                    return PartyProvider::SUCCESS;
                }, function(Server $server, int $success) use ($senderName, $playerName): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        case PartyProvider::SUCCESS:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-invite-player", $player, ["#player" => $playerName]));
                            $pk = new PlayerMessagePacket();
                            $pk->addData("players", $playerName);
                            $pk->addData("message", "&f&lRyZer&cBE &r".str_replace("§", "&", LanguageProvider::getMessageContainer("party-got-invite", $player, ["#player" => $player->getName()])));
                            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
                            break;
                        case PartyProvider::ALREADY_REQUEST:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-invite-already-request", $player, ["#player" => $playerName]));
                            break;
                        case PartyProvider::ALREADY_IN_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-already-party", $player, ["#player" => $playerName]));
                            break;
                        case PartyProvider::PLAYER_DOESNT_EXIST:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("unknown-player", $player, ["#player" => $playerName]));
                            break;
                        case PartyProvider::PARTY_FULL:
                            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("party-full", $player));
                            break;
                    }
                });
                break;
            case "delete":
                $senderName = $sender->getName();
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($senderName): int{
                    $party = PartyProvider::getPartyByPlayer($mysqli, $senderName);
                    if($party === null) return PartyProvider::NO_PARTY;
                    if(PartyProvider::getPlayerRole($mysqli, $senderName, false) !== PartyProvider::PARTY_ROLE_LEADER) return PartyProvider::NO_PERMISSION;
                    PartyProvider::deleteParty($mysqli, $senderName);
                    return PartyProvider::SUCCESS;
                }, function(Server $server, int $success) use ($senderName): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        case PartyProvider::SUCCESS:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-deleted", $player));
                            break;
                        case PartyProvider::NO_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-self-no-party", $player));
                            break;
                        case PartyProvider::NO_PERMISSION:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-no-permission", $player));
                            break;
                    }
                });
                break;
            case "accept":
                if(empty($args[1])){
                    $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Syntax error: /party accept <Party>");
                    return;
                }
                $partyOwner = $args[1];
                $senderName = $sender->getName();
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($partyOwner, $senderName): int{
                    if(!PartyProvider::hasRequest($mysqli, $partyOwner, $senderName)) return PartyProvider::NO_REQUEST;
                    $party = PartyProvider::getPartyByPlayer($mysqli, $partyOwner);
                    if($party === null) return PartyProvider::PARTY_DOESNT_EXIST;
                    $playerParty = PartyProvider::getPartyByPlayer($mysqli, $senderName);
                    if($playerParty !== null) return PartyProvider::ALREADY_IN_PARTY;
                    $memberCount = PartyProvider::getPartyMembers($mysqli, $partyOwner);
                    if(count($memberCount) >= Settings::$maxPartyPlayers) return PartyProvider::PARTY_FULL;
                    PartyProvider::removeRequest($mysqli, $partyOwner, $senderName);
                    PartyProvider::joinParty($mysqli, $senderName, $partyOwner);
                    return PartyProvider::SUCCESS;
                }, function(Server $server, int $success) use ($senderName, $partyOwner): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        case PartyProvider::SUCCESS:
                            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("party-accept", $player, ["#player" => $partyOwner]));
                            $message = LanguageProvider::getMessageContainer("party-joined", $player, ["#player" => $player->getName()]);
                            PartyProvider::sendPartyMessage($partyOwner, "&f&lRyZer&cBE &r".str_replace("§", "&", $message));
                            break;
                        case PartyProvider::NO_REQUEST:
                            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("party-no-request", $player, ["#player" => $partyOwner]));
                            break;
                        case PartyProvider::ALREADY_IN_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("party-self-already-party", $player));
                            break;
                        case PartyProvider::PARTY_DOESNT_EXIST:
                            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("party-doesnt-exist", $player));
                            break;
                        case PartyProvider::PARTY_FULL:
                            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("party-full", $player));
                            break;
                    }
                });
                break;
            case "join":
            case "enter":
                if(empty($args[1])){
                    $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Syntax error: /party join <Party>");
                    return;
                }
                $partyOwner = $args[1];
                $senderName = $sender->getName();
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($partyOwner, $senderName): int{
                    if(!PartyProvider::isPartyOpen($mysqli, $partyOwner)) return PartyProvider::PARTY_CLOSED;
                    $party = PartyProvider::getPartyByPlayer($mysqli, $partyOwner);
                    if($party === null) return PartyProvider::PARTY_DOESNT_EXIST;
                    $playerParty = PartyProvider::getPartyByPlayer($mysqli, $senderName);
                    if($playerParty !== null) return PartyProvider::ALREADY_IN_PARTY;
                    if(PartyProvider::isBannedFromParty($mysqli, $party, $senderName)) return PartyProvider::ALREADY_BANNED;

                    PartyProvider::removeRequest($mysqli, $partyOwner, $senderName);
                    PartyProvider::joinParty($mysqli, $senderName, $partyOwner);
                    return PartyProvider::SUCCESS;
                }, function(Server $server, int $success) use ($senderName, $partyOwner): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        case PartyProvider::SUCCESS:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-accept", $player, ["#player" => $partyOwner]));
                            $message = LanguageProvider::getMessageContainer("party-joined", $player, ["#player" => $player->getName()]);
                            PartyProvider::sendPartyMessage($partyOwner, "&f&lRyZer&cBE &r".str_replace("§", "&", $message));
                            break;
                        case PartyProvider::PARTY_CLOSED:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-party-closed-join", $player, ["#player" => $partyOwner]));
                            break;
                        case PartyProvider::ALREADY_IN_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-self-already-party", $player));
                            break;
                        case PartyProvider::PARTY_DOESNT_EXIST:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-doesnt-exist", $player));
                            break;
                        case PartyProvider::ALREADY_BANNED:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-banned", $player));
                            break;
                    }
                });
                break;
            case "leave":
                $senderName = $sender->getName();
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($senderName): int{
                    $playerParty = PartyProvider::getPartyByPlayer($mysqli, $senderName);
                    if($playerParty === null) return PartyProvider::NO_PARTY;
                    PartyProvider::leaveParty($mysqli, $senderName, $playerParty);
                    return $playerParty;
                }, function(Server $server, $success) use ($senderName): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        default:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-left", $player));
                            $message = LanguageProvider::getMessageContainer("party-left-info", $player, ["#player" => $player->getName()]);
                            PartyProvider::sendPartyMessage($success, "&f&lRyZer&cBE &r".str_replace("§", "&", $message));
                            break;
                        case PartyProvider::NO_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-self-no-party", $player));
                            break;
                    }
                });
                break;
            case "ban":
                if(empty($args[1])){
                    $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Syntax error: /party ban <Player>");
                    return;
                }
                $senderName = $sender->getName();
                $playerName = $args[1];
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($senderName, $playerName): int{
                    if(!PartyProvider::validPlayer($mysqli, $playerName)) return PartyProvider::PLAYER_DOESNT_EXIST;
                    $senderParty = PartyProvider::getPartyByPlayer($mysqli, $senderName);
                    if($senderParty === null) return PartyProvider::NO_PARTY;
                    $playerParty = PartyProvider::getPartyByPlayer($mysqli, $playerName);
                    if($playerParty !== null){
                        PartyProvider::leaveParty($mysqli, $playerName, $senderParty);
                    }
                    if(PartyProvider::isBannedFromParty($mysqli, $senderParty, $playerName)) return PartyProvider::ALREADY_BANNED;
                    if(PartyProvider::getPlayerRole($mysqli, $senderName, false) < PartyProvider::PARTY_ROLE_MODERATOR) return PartyProvider::NO_PERMISSION;
                    PartyProvider::banPlayerFromParty($mysqli, $senderParty, $senderName, $playerName);
                    return PartyProvider::SUCCESS;
                }, function(Server $server, int $success) use ($senderName, $playerName): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        case PartyProvider::SUCCESS:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-player-banned", $player, ["#player" => $playerName]));
                            break;
                        case PartyProvider::NO_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-self-no-party", $player));
                            break;
                        case PartyProvider::ALREADY_BANNED:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-already-banned", $player, ["#player" => $playerName]));
                            break;
                        case PartyProvider::NO_PERMISSION:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-no-permission", $player));
                            break;
                        case PartyProvider::PLAYER_DOESNT_EXIST:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("unknown-player", $player, ["#player" => $playerName]));
                            break;
                    }
                });
                break;
            case "unban":
                if(empty($args[1])){
                    $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Syntax error: /party unban <Player>");
                    return;
                }
                $senderName = $sender->getName();
                $playerName = $args[1];
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($senderName, $playerName): int{
                    $senderParty = PartyProvider::getPartyByPlayer($mysqli, $senderName);
                    if($senderParty === null) return PartyProvider::NO_PARTY;
                    if(!PartyProvider::isBannedFromParty($mysqli, $senderParty, $playerName)) return PartyProvider::ALREADY_UNBANNED;
                    if(PartyProvider::getPlayerRole($mysqli, $senderName, false) < PartyProvider::PARTY_ROLE_MODERATOR) return PartyProvider::NO_PERMISSION;
                    PartyProvider::unbanFromParty($mysqli, $senderParty, $playerName, $senderName);
                    return PartyProvider::SUCCESS;
                }, function(Server $server, int $success) use ($senderName, $playerName): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        case PartyProvider::SUCCESS:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-player-unbanned", $player, ["#player" => $playerName]));
                            break;
                        case PartyProvider::NO_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-self-no-party", $player));
                            break;
                        case PartyProvider::ALREADY_UNBANNED:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-already-unbanned", $player, ["#player" => $playerName]));
                            break;
                        case PartyProvider::NO_PERMISSION:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-no-permission", $player));
                            break;
                    }
                });
                break;
            case "kick":
                if(empty($args[1])){
                    $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Syntax error: /party kick <Player>");
                    return;
                }
                $senderName = $sender->getName();
                $playerName = $args[1];
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($senderName, $playerName): mixed{
                    $senderParty = PartyProvider::getPartyByPlayer($mysqli, $senderName);
                    if($senderParty === null) return PartyProvider::NO_PARTY;
                    $playerParty = PartyProvider::getPartyByPlayer($mysqli, $playerName);
                    if($playerParty !== $senderParty) return PartyProvider::NO_PARTY_PLAYER;
                    if(PartyProvider::getPlayerRole($mysqli, $senderName, false) < PartyProvider::PARTY_ROLE_MODERATOR) return PartyProvider::NO_PERMISSION;
                    PartyProvider::leaveParty($mysqli, $playerName, $senderParty);
                    return $playerParty;
                }, function(Server $server, mixed $success) use ($senderName, $playerName): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        default:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-player-kicked", $player, ["#player" => $playerName]));
                            $message = LanguageProvider::getMessageContainer("party-kicked", $player, ["#player" => $playerName]);
                            PartyProvider::sendPartyMessage($success, str_replace("§", "&", $message));
                            break;
                        case PartyProvider::NO_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-self-no-party", $player));
                            break;
                        case PartyProvider::NO_PARTY_PLAYER:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-no-party-player", $player, ["#player" => $playerName]));
                            break;
                        case PartyProvider::NO_PERMISSION:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-no-permission", $player));
                            break;
                    }
                });
                break;
            case "public":
            case "open":
                $senderName = $sender->getName();
                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($senderName): int{
                    $party = PartyProvider::getPartyByPlayer($mysqli, $senderName);
                    if($party === null) return PartyProvider::NO_PARTY;
                    if(PartyProvider::isPartyOpen($mysqli, $party)){
                        PartyProvider::openParty($mysqli, $party, false);
                        return PartyProvider::PARTY_CLOSE;
                    }
                    if(PartyProvider::getPlayerRole($mysqli, $senderName, false) < PartyProvider::PARTY_ROLE_MODERATOR) return PartyProvider::NO_PERMISSION;
                    PartyProvider::openParty($mysqli, $party);
                    return PartyProvider::SUCCESS;
                }, function(Server $server, int $success) use ($senderName): void{
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    switch($success){
                        case PartyProvider::NO_PARTY:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-no-party", $player));
                            break;
                        case PartyProvider::PARTY_CLOSE:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-close", $player));
                            break;
                        case PartyProvider::NO_PERMISSION:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-no-permission", $player));
                            break;
                        case PartyProvider::SUCCESS:
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer("party-open", $player));
                            break;
                    }
                });
                break;
            default:
                $subCommands = [
                    "invite" => "<Player>",
                    "accept" => "<Request>",
                    "leave" => "",
                    "join" => "<PlayerName>",
                    "ban" => "<PlayerName>",
                    "unban" => "<PlayerName>",
                    "public" => ""
                ];

                $helpList = [];
                foreach($subCommands as $subCommand => $arguments) {
                    $helpList[] = TextFormat::LIGHT_PURPLE."/party ".TextFormat::RED.$subCommand." ".TextFormat::LIGHT_PURPLE.$arguments.TextFormat::DARK_GRAY." | ".TextFormat::WHITE.LanguageProvider::getMessageContainer("party-help-$subCommand", $sender);
                }

                $sender->sendMessage(implode("\n".TextFormat::RESET, $helpList));
                break;
        }
    }
}