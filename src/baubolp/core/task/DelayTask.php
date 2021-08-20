<?php


namespace baubolp\core\task;


use baubolp\core\provider\JoinMEProvider;
use pocketmine\scheduler\Task;

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