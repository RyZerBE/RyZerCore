<?php

namespace ryzerbe\core\event\player\game;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use ryzerbe\core\player\RyZerPlayer;

class JoinMeCreateEvent extends PlayerEvent implements Cancellable {
    private RyZerPlayer $ryZerPlayer;

    public function __construct(RyZerPlayer $ryZerPlayer){
        $this->ryZerPlayer = $ryZerPlayer;
    }

    public function getRyZerPlayer(): RyZerPlayer{
        return $this->ryZerPlayer;
    }
}