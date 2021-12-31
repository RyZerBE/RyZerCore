<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\TaskUtils;

class InvBugFixCommand extends Command {

    public function __construct(){
        parent::__construct("invbug", "fix your invbug", "", []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;

        $pk = new ContainerClosePacket();
        $pk->windowId = ContainerIds::INVENTORY;
        $sender->dataPacket($pk);

        $pk->windowId = ContainerIds::FIXED_INVENTORY;
        $sender->dataPacket($pk);
        $sender->sendMessage("FIX");
    }
}