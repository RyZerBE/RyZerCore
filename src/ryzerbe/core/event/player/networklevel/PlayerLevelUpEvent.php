<?php

namespace ryzerbe\core\event\player\networklevel;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerLevelUpEvent extends PlayerEvent {
    private int $level;

    public function __construct(Player $player, int $level){
        $this->player = $player;
        $this->level = $level;
    }

    public function getLevel(): int{
        return $this->level;
    }
}