<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;

class ChunkFixCommand extends Command {

    public function __construct(){
        parent::__construct("fix", "fix broken chunks", "", []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;
        if($sender->hasDelay("chunkfix_command")) return;

        $sender->addDelay("chunkfix_command", 5);
        $pk = new ChunkRadiusUpdatedPacket();
        $pk->radius = 1;
        $sender->dataPacket($pk);
        $pk->radius = 2;
        $sender->dataPacket($pk);
        $sender->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("chunkfix-command", $sender));
        AsyncExecutor::submitClosureTask(40, function(int $currentTick) use ($sender): void{
            if(!$sender->isConnected()) return;

            $pk = new ChunkRadiusUpdatedPacket();
            $pk->radius = 6;
            $sender->dataPacket($pk);
        });
    }
}