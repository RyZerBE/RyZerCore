<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
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
        if(!$sender->isOp()) {
            $sender->sendMessage(TextFormat::RED."Dieser Command funktioniert leider noch nicht :(");
            $sender->sendMessage(TextFormat::RED."Bitte joine dem Netzwerk neu, falls du den Invbug hast.");
            return;
        }
        $position = $sender->asPosition();
        if(!$sender->hasDelay("change_dimension")){
            $sender->teleport($position);
            $pk = new ChangeDimensionPacket();
            $pk->position = $position;
            $pk->dimension = DimensionIds::OVERWORLD;
            $pk->respawn = true;
            $sender->dataPacket($pk);
            $sender->addDelay("change_dimension", 4);
            AsyncExecutor::submitClosureTask(TaskUtils::secondsToTicks(2), function(int $currentTick) use ($sender, $position): void{
                if(!$sender->isConnected()) return;
                $sender->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);
            });
        }
    }
}