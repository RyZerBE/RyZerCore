<?php

namespace ryzerbe\core\event\player\rank;

use pocketmine\event\Event;
use pocketmine\Player;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\rank\Rank;

class PlayerRankUpdateEvent extends Event {

    private RyZerPlayer $ryzerPlayer;
    private Rank $rank;

    /**
     * @param RyZerPlayer $ryZerPlayer
     * @param Rank $rank
     */
    public function __construct(RyZerPlayer $ryZerPlayer, Rank $rank){
        $this->ryzerPlayer = $ryZerPlayer;
        $this->rank = $rank;
    }

    /**
     * @return Rank
     */
    public function getRank(): Rank{
        return $this->rank;
    }

    /**
     * @return RyZerPlayer
     */
    public function getRyZerPlayer(): RyZerPlayer{
        return $this->ryzerPlayer;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player{
        return $this->ryzerPlayer->getPlayer();
    }
}