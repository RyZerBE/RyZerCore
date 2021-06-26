<?php


namespace baubolp\core\task;


use baubolp\core\provider\ChatModProvider;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class UnblockIpTask extends Task
{

    public function onRun(int $currentTick)
    {
       Server::getInstance()->getNetwork()->unblockAddress("5.181.151.61");
       foreach (ChatModProvider::$wait as $playerName => $time) {
           if(time() > $time) {
               unset(ChatModProvider::$wait[$playerName]);
           }
       }
    }
}