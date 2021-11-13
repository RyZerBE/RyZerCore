<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use ryzerbe\core\form\types\PlayerSettingsForm;
use ryzerbe\core\player\PMMPPlayer;

class PlayerSettingsCommand extends Command {

    public function __construct(){
        parent::__construct("settings", "update your settings", "", []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;

        PlayerSettingsForm::onOpen($sender);
    }
}