<?php

namespace ryzerbe\core\event\player;

use pocketmine\event\player\PlayerEvent;
use ryzerbe\core\player\RyZerPlayer;

class RyZerPlayerAuthEvent extends PlayerEvent {

    /** @var RyZerPlayer  */
    private RyZerPlayer $ryZerPlayer;

    /**
     * @param RyZerPlayer $player
     */
    public function __construct(RyZerPlayer $player){
        $this->ryZerPlayer = $player;
    }

    /**
     * @return RyZerPlayer
     */
    public function getRyZerPlayer(): RyZerPlayer{
        return $this->ryZerPlayer;
    }
}