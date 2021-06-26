<?php


namespace baubolp\core\command;


use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\ModerationProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class WarnCommand extends Command
{

    public function __construct()
    {
        parent::__construct('warn', "warn a player about a mistake", "", ['']);
        $this->setPermission("core.warn");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender)) return;

        if(empty($args[0]) || empty($args[1])) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/warn <Player> <ReasonID>");
            return;
        }

        if(empty(Ryzer::$banIds[$args[1]])) {
            ModerationProvider::sendBanIDList($sender);
            return;
        }

        if(($player = Server::getInstance()->getPlayerExact($args[0]))) {
            $player->sendMessage("\n\n\n\n\n\n\n\n".Ryzer::PREFIX.LanguageProvider::getMessageContainer('player-warn', $player->getName(), ['#reason' => Ryzer::$banIds[$args[1]]['banreason']]));
            $player->playSound("ambient.weather.thunder", 5, 1.0, [$player]);
            $player->sendTitle(TextFormat::RED."WARN!");
            ModerationProvider::addWarn($player->getName(), $sender->getName(), Ryzer::$banIds[$args[1]]['banreason']);
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Der Spieler wurde verwarnt.");
            return;
        }

        $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Spieler ist nicht online.");
    }
}