<?php

namespace ryzerbe\core\player\networklevel\reward;

use Closure;
use ryzerbe\core\player\networklevel\LevelUpReward;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\provider\CoinProvider;

class CoinReward extends LevelUpReward {

    public function __construct(int $level, int $coins){
        parent::__construct($level, $coins." Coins", function(int $level, RyZerPlayer $ryZerPlayer) use ($coins): void{
            CoinProvider::addCoins($ryZerPlayer->getPlayer()->getName(), $coins);
        });
    }
}