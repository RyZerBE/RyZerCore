<?php

namespace ryzerbe\core\event\player\coin;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerCoinsAddEvent extends PlayerEvent implements Cancellable {
    private int $coins;
    private bool $boosted;

    public function __construct(Player $player, int $coins, bool $isBoost){
        $this->player = $player;
        $this->coins = $coins;
        $this->boosted = $isBoost;
    }

    public function getAddedCoins(): int{
        return $this->coins;
    }

    /**
     * @return bool
     */
    public function isBoosted(): bool{
        return $this->boosted;
    }
}