<?php


namespace baubolp\core\command;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class GameTimeCommand extends Command
{

    public function __construct()
    {
        parent::__construct("gametime", "See your playtime", "", ['gt', 'ot', 'onlinetime']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        if(($obj = RyzerPlayerProvider::getRyzerPlayer($sender->getName())) != null) {
            $gameTime = $obj->getOnlineTime();
            $sender->sendMessage(Ryzer::PREFIX.$gameTime);
        }
    }
}