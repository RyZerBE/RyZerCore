<?php


namespace baubolp\core\command;


use baubolp\core\provider\ModerationProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BanCommand extends Command
{

    const SAVE_PLAYRES = [
        'BauboLPYT'
    ];

    public function __construct()
    {
        parent::__construct('ban', "punish a player from the network", "", ['punish']);
        $this->setPermission('core.ban');
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender)) return;

        if(empty($args[0]) || empty($args[1])) {
            ModerationProvider::sendBanIDList($sender);
            return;
        }

        $playerName = $args[0];
        $banId = $args[1];

        if(in_array($playerName, self::SAVE_PLAYRES)) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Scherzkeks.. Ban/Mute doch nicht den Administrator ;)");
            return;
        }

        if(empty(Ryzer::$banIds[$banId])) {
            ModerationProvider::sendBanIDList($sender);
            return;
        }

        $banData = Ryzer::$banIds[$banId];
        $banId = ModerationProvider::generateBanId();

        ModerationProvider::createProof($banId);
        if($banData['type'] == 1) {
            ModerationProvider::setBan($playerName, $banData, ModerationProvider::getPoints($playerName), $banId, $sender->getName());
            $sender->sendMessage(Ryzer::PREFIX."Der Spieler ".TextFormat::AQUA.$playerName.TextFormat::WHITE." wurde für den Grund ".TextFormat::AQUA.$banData['banreason'].TextFormat::WHITE." gebannt. ".TextFormat::DARK_GRAY."[".TextFormat::YELLOW.$banId.TextFormat::DARK_GRAY."]");
        }else {
            ModerationProvider::setMute($playerName, $banData, ModerationProvider::getPoints($playerName, false), $banId, $sender->getName());
            $sender->sendMessage(Ryzer::PREFIX . "Der Spieler " . TextFormat::AQUA . $playerName . TextFormat::WHITE . " wurde für den Grund " . TextFormat::AQUA . $banData['banreason'] . TextFormat::WHITE . " gemutet.");
        }
    }
}