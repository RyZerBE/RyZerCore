<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\UserInfoForm;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\RyZerBE;

class UserInfoCommand extends Command {

    public function __construct(){
        parent::__construct("userinfo", "look information about a user", "", ["lookup"]);
        $this->setPermission("ryzer.userinfo");
        $this->setPermissionMessage(RyZerBE::PREFIX.TextFormat::RED."did hier sind vertrauliche Daten! Finger weg..");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;
        if(!$this->testPermission($sender)) return;

        if(empty($args[0])) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."/userinfo <Spieler>");
            return;
        }

        UserInfoForm::onOpen($sender, $args[0]);
    }
}