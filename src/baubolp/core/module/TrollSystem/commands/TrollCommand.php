<?php


namespace baubolp\core\module\TrollSystem\commands;


use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\module\TrollSystem\TrollSystem;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class TrollCommand extends Command
{

    public function __construct()
    {
        parent::__construct("troll", "Troll another players", "", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if($sender->hasPermission("troll.look") && isset($args[0])) {
            if($args[0] == "list") {
                $sender->sendMessage(TrollSystem::Prefix.TextFormat::GREEN.implode(", ", Ryzer::getTrollSystem()->trollPlayers));
                return;
            }
        }

        var_dump(CloudBridge::getCloudProvider()->isServerPrivate(CloudProvider::getServer()));
        var_dump(CloudBridge::getCloudProvider()->isServerPrivate(CloudProvider::getServer()) != $sender->getName());
        if(CloudBridge::getCloudProvider()->isServerPrivate(CloudProvider::getServer()) != $sender->getName() && !$sender->isOp()) {
            $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('command-not-found', $sender->getName()));
            return;
        }

        if(isset($args[0]) && isset($args[1])) {
            if($args[0] == "trollmode") {
                if(($player = Server::getInstance()->getPlayerExact($args[1]))) {
                    if(in_array($player->getName(), Ryzer::getTrollSystem()->trollPlayers)) {
                        Ryzer::getTrollSystem()->removeTrollPlayer($player->getName());
                        $sender->sendMessage(TrollSystem::Prefix."The player can now no longer troll other players!");
                    }else {
                        Ryzer::getTrollSystem()->addTrollPlayer($player->getName());
                        $player->sendMessage(TrollSystem::Prefix.TextFormat::DARK_AQUA."TROLOLOLOLOL");
                        $player->getInventory()->addItem(Item::get(Item::COOKIE)->setCustomName(TextFormat::RED."Troll-Item"));
                        $sender->sendMessage(TrollSystem::Prefix."The player can now troll too!");
                    }
                }
            }else {
                $sender->sendMessage(TrollSystem::Prefix."/troll | /troll trollmode <PlayerName>");
            }
        }else {
            $sender->sendMessage(TrollSystem::Prefix."/troll trollmode <PlayerName> - Activate TrollMode for other players!");
        }

        if(in_array($sender->getName(), Ryzer::getTrollSystem()->getTrollPlayers())) {
            $sender->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-mode-deactivated', $sender->getName()));
            Ryzer::getTrollSystem()->removeTrollPlayer($sender->getName());
        }else {
            $sender->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-mode-activated', $sender->getName()));
            Ryzer::getTrollSystem()->addTrollPlayer($sender->getName());
            $sender->sendMessage(TrollSystem::Prefix.TextFormat::DARK_AQUA."TROLOLOLOLOL");
            $sender->getInventory()->addItem(Item::get(Item::COOKIE)->setCustomName(TextFormat::RED."Troll-Item"));
        }
    }
}