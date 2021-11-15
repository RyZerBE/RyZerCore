<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use ryzerbe\core\form\types\report\ReportPlayerForm;
use ryzerbe\core\form\types\report\StaffMainForm;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\provider\StaffProvider;

class ReportCommand extends Command {

    public function __construct(){
        parent::__construct("report", "", "", []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof PMMPPlayer) return;

        if(StaffProvider::loggedIn($sender)) {
            StaffMainForm::onOpen($sender);
            return;
        }

        ReportPlayerForm::onOpen($sender);
    }
}