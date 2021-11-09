<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use ryzerbe\core\form\types\SettingsForm;
use ryzerbe\core\player\PMMPPlayer;

class SettingsCommand extends Command {

    public function __construct(){
        parent::__construct("settings", "settings for your game feeling", "", []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof PMMPPlayer) return;

        SettingsForm::onOpen($sender);
    }
}