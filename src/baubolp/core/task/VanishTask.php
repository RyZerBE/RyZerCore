<?php


namespace baubolp\core\task;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\VanishProvider;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class VanishTask extends Task
{

    /**
     * @inheritDoc
     */
    public function onRun(int $currentTick)
    {
        foreach (VanishProvider::$vanishedPlayer as $playerName) {
            if(($player = Server::getInstance()->getPlayerExact($playerName)) != null) {
              if(($ryzerPlayer = RyzerPlayerProvider::getRyzerPlayer($player->getName())) != null)
                VanishProvider::vanishPlayer($ryzerPlayer, true);
            }
        }

        foreach(RyzerPlayerProvider::getRyzerPlayers() as $player) {
            $player->gameTimeTicks++;
        }
    }
}