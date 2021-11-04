<?php


namespace baubolp\core\listener;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\AsyncExecutor;
use baubolp\core\provider\GameTimeProvider;
use baubolp\core\provider\JoinMEProvider;
use baubolp\core\provider\MySQLProvider;
use mysqli;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class PlayerQuitListener implements Listener
{

    public function playerQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $ryzerPlayer = RyzerPlayerProvider::getRyzerPlayer($player->getName());
        if($ryzerPlayer === null) return;
        $ticks = $ryzerPlayer->gameTimeTicks;
        $playerName = $player->getName();
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($ticks, $playerName): void{
            $mysqli->query("UPDATE GameTime SET `ticks`='$ticks' WHERE playername='$playerName'");
        });
        RyzerPlayerProvider::unregisterRyzerPlayer($event->getPlayer()->getName());
        JoinMEProvider::removeJoinMe($event->getPlayer()->getName(), true);
    }
}