<?php


namespace baubolp\core\command;


use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PKickCommand extends Command
{

    public function __construct()
    {
        parent::__construct("pkick", "kick a people from your private server", "", []);
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(CloudBridge::getCloudProvider()->isServerPrivate(CloudProvider::getServer()) != $sender->getName() && !$sender->isOp()) {
            $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('command-not-found', $sender->getName()));
            return;
        }

        if(empty($args[0])) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/pkick <PlayerName>");
            return;
        }

        if(($player = Server::getInstance()->getPlayer($args[0])) != null) {
            $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('private-server-kick', $player->getName(), ["#owner" => $sender->getName()]));
            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "hub");
        }else {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Dieser Spieler ist nicht online.");
        }
    }
}