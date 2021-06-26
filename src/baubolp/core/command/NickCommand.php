<?php


namespace baubolp\core\command;


use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\provider\RankProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class NickCommand extends Command
{

    public function __construct()
    {
        Ryzer::addPermission("core.nick");
        parent::__construct("nick", "hide your identity", "", []);
        $this->setPermission("core.nick");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        if(stripos(CloudProvider::getServer(), "CWBW") !== false) {
            $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('cannot-nick-in-cwbw', $sender->getName()));
            return;
        }

        if(($obj = RyzerPlayerProvider::getRyzerPlayer($sender->getName())) != null) {
            if($obj->getNick() == null) {
                Ryzer::getNickProvider()->setNick($sender, $obj);
                $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('nick-set', $sender->getName()));
                Ryzer::getNickProvider()->showNickToTeam($sender);
            }else {
                Ryzer::getNickProvider()->removeNick($sender, $obj);
                $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('nick-removed', $sender->getName()));
            }
        }
    }
}