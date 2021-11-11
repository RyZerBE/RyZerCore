<?php

namespace ryzerbe\core\command;

use BauboLP\Cloud\Bungee\BungeeAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\RyZerBE;
use function implode;

class TeamchatCommand extends Command {
    public function __construct(){
        parent::__construct("teamchat", "broadcast message to all teammembers", "", ['tc']);
        $this->setPermission("ryzer.teamchat");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;
        if(!StaffProvider::loggedIn($sender)){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Das Einloggen in unsere Systeme ist Voraussetzung, um den Teamchat nutzen zu können!");
            $sender->playSound('note.bass', 5.0, 2.0, [$sender]);
            return;
        }
        if(empty($args[0])){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "/tc <Message>");
            return;
        }
        $message = implode(" ", $args);
        $message = TextFormat::RED . TextFormat::BOLD . "TeamChat " . TextFormat::RESET . TextFormat::YELLOW . $sender->getName() . TextFormat::RESET . TextFormat::DARK_GRAY . " | " . TextFormat::WHITE . $message;
        foreach(StaffProvider::getLoggedIn() as $staff){
            BungeeAPI::sendMessage($message, $staff);
        }
    }
}