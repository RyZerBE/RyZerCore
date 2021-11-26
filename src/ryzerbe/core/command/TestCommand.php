<?php

namespace ryzerbe\core\command;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use ryzerbe\core\util\SkinUtils;

class TestCommand extends Command {

    public function __construct(){
        parent::__construct("test", "player head test", "", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;

        $form = new SimpleForm(function(Player $player, $data): void{});
        $form->addButton($sender->getName()." Head", 1, SkinUtils::getPlayerHeadIcon($sender->getName()));
        $form->sendToPlayer($sender);
    }
}