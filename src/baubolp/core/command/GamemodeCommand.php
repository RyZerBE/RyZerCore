<?php


namespace baubolp\core\command;


use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class GamemodeCommand extends Command
{

    public function __construct()
    {
        parent::__construct("gamemode", "Change your gamemode", "", ["gm"]);
        $this->setPermission("core.gamemode");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."Keine Chance bro! ;c");
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        if(empty($args[0])) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::YELLOW."/gm <0|1|2|3> (PlayerName)");
            return;
        }

        $gm = Server::getGamemodeFromString($args[0]);
        if($gm === -1) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::YELLOW."/gm <0|1|2|3> (PlayerName)");
            return;
        }

        if($gm == Player::CREATIVE) {
            if(!$sender->hasPermission("core.gamemode.1")) {
                $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Denkst auch, du wärst Gott hahahahaha");
                return;
            }
        }

        if(empty($args[1])) {
            $sender->setGamemode($gm);
            $sender->sendMessage(Ryzer::PREFIX."Deine Spielmodus wurde aktualisiert.");
            return;
        }

        if(!$sender->hasPermission("core.gamemode.other")) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Du darfst nur deinen eigenen Gamemode ändern.");
            return;
        }

        $player = Server::getInstance()->getPlayer($args[1]);
        if($player === null) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Dieser Spieler ist offline.");
            return;
        }

        $player->setGamemode($gm);
        $player->sendMessage(Ryzer::PREFIX."Deine Spielmodus wurde aktualisiert.");
        $sender->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Der Spielmodus von ".TextFormat::GOLD.$player->getName().TextFormat::GREEN." wurde aktualisiert.");
    }
}