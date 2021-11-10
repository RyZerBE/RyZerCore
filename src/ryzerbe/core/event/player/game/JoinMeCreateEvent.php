<?php

namespace ryzerbe\core\event\player\game;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use ryzerbe\core\player\RyZerPlayer;

class JoinMeCreateEvent extends PlayerEvent implements Cancellable {

    /** @var RyZerPlayer  */
    private RyZerPlayer $ryZerPlayer;

    /**
     * @param RyZerPlayer $ryZerPlayer
     */
    public function __construct(RyZerPlayer $ryZerPlayer){
        $this->ryZerPlayer = $ryZerPlayer;
    }

    /**
     * @return RyZerPlayer
     */
    public function getRyZerPlayer(): RyZerPlayer{
        return $this->ryZerPlayer;
    }
}