<?php

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use ryzerbe\core\event\player\coin\PlayerCoinsAddEvent;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;

class PlayerAddCoinsListener implements Listener {

    /**
     * @param PlayerCoinsAddEvent $event
     * @priority LOWEST
     */
    public function addCoins(PlayerCoinsAddEvent $event){
        if($event->isBoosted()) return;

        $player = $event->getPlayer();
        if(!$player instanceof PMMPPlayer) return;
        $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($event->getPlayer());
        if($rbePlayer === null) return;
        $coinBoost = $rbePlayer->getCoinboost();
        if($coinBoost === null) return;

        if($coinBoost->isValid()){
            $event->setCancelled();
            $coinBoost->boostCoins($player, $event->getAddedCoins());
        }
    }
}