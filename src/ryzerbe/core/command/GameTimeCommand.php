<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\RyZerBE;

class GameTimeCommand extends Command {
    public function __construct(){
        parent::__construct("gametime", "View your onlinetime on RyZerBE", "", ["onlinetime", "gt", "ot"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;
        $ryzerPlayer = $sender->getRyZerPlayer();
        if($ryzerPlayer === null) return;
        $sender->sendMessage(RyZerBE::PREFIX . $ryzerPlayer->getOnlineTime());
    }
}