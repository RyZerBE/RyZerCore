<?php

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use pocketmine\Server;
use ryzerbe\core\event\player\coin\PlayerCoinsAddEvent;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\util\async\AsyncExecutor;

class PlayerAddCoinsListener implements Listener {

    /**
     * @param PlayerCoinsAddEvent $event
     * @priority LOWEST
     */
    public function addCoins(PlayerCoinsAddEvent $event){
        if($event->isBoosted()) return;

        $player = $event->getPlayer();
        $playerName = $player->getName();
        if(!$player instanceof PMMPPlayer) return;
        $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($event->getPlayer());
        if($rbePlayer === null) return;
        $coinBoost = $rbePlayer->getCoinboost();
        if($coinBoost === null) return;

        if($coinBoost->isValid()){
            $event->setCancelled();
            $coinBoost->boostCoins($player, $event->getAddedCoins());
            if($coinBoost->isForAll()) {
                foreach(RyZerPlayerProvider::getRyzerPlayers() as $ryzerPlayer) {
                    $coinBoost->boostCoins($ryzerPlayer->getPlayer(), $event->getAddedCoins());
                }
            }
        }else {
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(\mysqli $mysqli) use ($playerName): void{
                $mysqli->query("DELETE FROM `coinboosts` WHERE player='$playerName'");
            });
        }
    }
}