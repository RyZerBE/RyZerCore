<?php


namespace baubolp\core\command;


use baubolp\core\Ryzer;
use baubolp\core\task\LoadLookUpInformations;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class LookCommand extends Command
{

    public function __construct()
    {
        parent::__construct('look', "see private information about a player", "", []);
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
        $this->setPermission("core.look");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) {
            return;
        }

        if(!$this->testPermission($sender)) return;

        if(empty($args[0])) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/look <PlayerName>");
            return;
        }

        $sender->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Die Abfrage der Daten kann einen Moment dauern...");
        Server::getInstance()->getAsyncPool()->submitTask(new LoadLookUpInformations($args[0], $sender->getName()));
    }
}