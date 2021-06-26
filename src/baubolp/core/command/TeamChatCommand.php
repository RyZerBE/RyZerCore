<?php


namespace baubolp\core\command;


use BauboLP\Cloud\Bungee\BungeeAPI;
use baubolp\core\provider\StaffProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TeamChatCommand extends Command
{

    public function __construct()
    {
        parent::__construct("teamchat", "send a message to all teammembers", "", ['tc']);
        $this->setPermission("core.tc");
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        if(!StaffProvider::isLogin($sender->getName())) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Das Einloggen in unsere Systeme ist Voraussetzung, um den Teamchat nutzen zu können!");
            $sender->playSound('note.bass', 5.0, 2.0, [$sender]);
            return;
        }

        if(empty($args[0])) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/tc <Message>");
            return;
        }

        $message = implode(" ", $args);
        $message = TextFormat::RED.TextFormat::BOLD."TeamChat ".TextFormat::RESET.TextFormat::YELLOW.$sender->getName().TextFormat::RESET.TextFormat::DARK_GRAY." | ".TextFormat::WHITE.$message;

        foreach (StaffProvider::getLoggedStaff() as $staff) {
            BungeeAPI::sendMessage($message, $staff);
        }
    }
}