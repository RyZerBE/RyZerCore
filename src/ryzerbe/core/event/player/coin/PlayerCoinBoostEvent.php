<?php

namespace ryzerbe\core\event\player\coin;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;
use ryzerbe\core\util\Coinboost;

class PlayerCoinBoostEvent extends PlayerEvent {

    private Coinboost $coinboost;

    public function __construct(Player $booster, Coinboost $coinboost){
        $this->player = $booster;
        $this->coinboost = $coinboost;
    }

    /**
     * @return Coinboost
     */
    public function getCoinboost(): Coinboost{
        return $this->coinboost;
    }
}