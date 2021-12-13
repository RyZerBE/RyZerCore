<?php

namespace ryzerbe\core\event\player;

use pocketmine\event\player\PlayerEvent;
use ryzerbe\core\player\RyZerPlayer;

class RyZerPlayerAuthEvent extends PlayerEvent {
    private RyZerPlayer $ryZerPlayer;

    public function __construct(RyZerPlayer $player){
        $this->player = $player->getPlayer();
        $this->ryZerPlayer = $player;
    }

    public function getRyZerPlayer(): RyZerPlayer{
        return $this->ryZerPlayer;
    }
}