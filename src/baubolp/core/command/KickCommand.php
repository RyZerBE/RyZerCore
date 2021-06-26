<?php


namespace baubolp\core\command;


use BauboLP\Cloud\Bungee\BungeeAPI;
use baubolp\core\provider\ModerationProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class KickCommand extends Command
{
    const SAVE_PLAYRES = [
        'BauboLPYT'
    ];

    public function __construct()
    {
        parent::__construct("kick", "kick a player from the network", "", []);
        $this->setPermission("core.kick");
        $this->setPermissionMessage(Ryzer::PREFIX."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(empty($args[0]) || empty($args[1])) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/kick <Spieler> <ID>");
            return;
        }

        $banId = $args[1];
        $playerName = $args[0];

        if(in_array($playerName, self::SAVE_PLAYRES)) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Scherzkeks.. Ban/Mute doch nicht den Administrator ;)");
            return;
        }
        if(empty(Ryzer::$banIds[$banId])) {
            ModerationProvider::sendBanIDList($sender);
            return;
        }

        $banData = Ryzer::$banIds[$banId];
        $sender->sendMessage(Ryzer::PREFIX."Der Spieler ".TextFormat::AQUA.$playerName.TextFormat::WHITE." wurde für ".TextFormat::AQUA.$banData['banreason'].TextFormat::WHITE." vom Netzwerk gekickt.");
        BungeeAPI::kickPlayer($playerName, Ryzer::PREFIX.TextFormat::RED."You were kicked! ".TextFormat::YELLOW."Reason: ".TextFormat::AQUA.$banData['banreason']);
    }
}