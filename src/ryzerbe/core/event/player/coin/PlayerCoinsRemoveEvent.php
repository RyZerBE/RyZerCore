<?php

namespace ryzerbe\core\event\coin;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerCoinsRemoveEvent extends PlayerEvent {

    /** @var int */
    private int $coins;

    public function __construct(Player $player, int $coins){
        $this->player = $player;
        $this->coins = $coins;
    }

    /**
     * @return int
     */
    public function getRemovedCoins(): int{
        return $this->coins;
    }
}