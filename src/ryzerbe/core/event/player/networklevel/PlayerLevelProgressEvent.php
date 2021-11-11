<?php

namespace ryzerbe\core\event\player\networklevel;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerLevelProgressEvent extends PlayerEvent {
    private int $progress;

    public function __construct(Player $player, int $progress){
        $this->player = $player;
        $this->progress = $progress;
    }

    public function getProgress(): int{
        return $this->progress;
    }
}