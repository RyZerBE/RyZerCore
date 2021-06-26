<?php


namespace baubolp\core\task;


use baubolp\core\provider\JoinMEProvider;
use baubolp\core\Ryzer;
use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class DelayTask extends Task
{

    public function onRun(int $currentTick)
    {
        foreach (JoinMEProvider::$joinMe as $playerName => $time) {
            if(time() > $time) {
                JoinMEProvider::removeJoinMe($playerName);
            }
        }
    }
}