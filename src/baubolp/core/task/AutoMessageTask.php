<?php


namespace baubolp\core\task;


use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class AutoMessageTask extends Task
{
    /** @var int  */
    private $i = 0;

    /**
     * @inheritDoc
     */
    public function onRun(int $currentTick)
    {
        $this->i++;
        if($this->i <= 5) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player)
                $player->sendMessage("\n\n\n\n".Ryzer::PREFIX.LanguageProvider::getMessageContainer("automessage-{$this->i}", $player->getName()));
        }else
            $this->i = 0;
    }
}