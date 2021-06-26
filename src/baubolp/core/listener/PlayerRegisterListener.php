<?php


namespace baubolp\core\listener;


use baubolp\core\listener\own\PlayerRegisterEvent;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\task\LoadAsyncDataTask;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

class PlayerRegisterListener implements Listener
{

    public function registerPlayer(PlayerRegisterEvent $event)
    {
        MainLogger::getLogger()->info($event->getPlayer()->getName()." registered");
        Server::getInstance()->getAsyncPool()->submitTask(new LoadAsyncDataTask($event->getRyzerPlayer()->getLoginData()->getDataArray(), MySQLProvider::getMySQLData()));
    }
}