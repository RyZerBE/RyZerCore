<?php

namespace baubolp\core\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerLevelProgressEvent extends PlayerEvent {

    /** @var int  */
    private int $progress;

    /**
     * PlayerLevelProgressEvent constructor.
     * @param Player $player
     * @param int $progress
     */
    public function __construct(Player $player, int $progress){
        $this->player = $player;
        $this->progress = $progress;
    }

    /**
     * @return int
     */
    public function getProgress(): int{
        return $this->progress;
    }
}