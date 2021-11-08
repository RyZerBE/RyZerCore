<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use ryzerbe\core\form\types\LanguageForm;

class LanguageCommand extends Command {

    public function __construct(){
        parent::__construct("language", "switch your language", "", ["lang"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player) return;
        LanguageForm::onOpen($sender);
    }
}