<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\punishment\PunishmentMainForm;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\RyZerBE;

class BanUiCommand extends Command {

    public function __construct(){
        parent::__construct("banui", "open ui to punish easier", "", ["punishui"]);
        $this->setPermission("ryzer.ban");
        $this->setPermissionMessage(RyZerBE::PREFIX.TextFormat::RED."No Permissions");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof PMMPPlayer) return;

        PunishmentMainForm::onOpen($sender);
    }
}