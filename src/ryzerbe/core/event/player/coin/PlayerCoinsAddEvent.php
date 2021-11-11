<?php

namespace ryzerbe\core\event\player\coin;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerCoinsAddEvent extends PlayerEvent {
    private int $coins;

    public function __construct(Player $player, int $coins){
        $this->player = $player;
        $this->coins = $coins;
    }

    public function getAddedCoins(): int{
        return $this->coins;
    }
}