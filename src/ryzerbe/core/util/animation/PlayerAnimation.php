<?php

declare(strict_types=1);

namespace ryzerbe\core\util\animation;

use pocketmine\Player;

class PlayerAnimation extends Animation {
    protected Player $player;

    public function __construct(Player $player){
        $this->player = $player;
        parent::__construct();
    }

    public function getPlayer(): Player{
        return $this->player;
    }
}