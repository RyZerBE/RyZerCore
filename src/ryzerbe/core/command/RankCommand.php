<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\rank\RankMainForm;

class RankCommand extends Command {

    public function __construct(){
        parent::__construct("rank", "rank admin command", "");
        $this->setPermission("ryzer.rank");
        $this->setPermissionMessage(TextFormat::RED."No Permission!");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player) return;

        RankMainForm::onOpen($sender);
    }
}