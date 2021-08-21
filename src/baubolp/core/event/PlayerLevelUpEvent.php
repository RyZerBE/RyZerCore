<?php

namespace baubolp\core\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerLevelUpEvent extends PlayerEvent {

    /** @var int  */
    private int $level;

    public function __construct(Player $player, int $level){
        $this->player = $player;
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getLevel(): int{
        return $this->level;
    }
}