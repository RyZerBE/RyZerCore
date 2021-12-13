<?php

namespace ryzerbe\core\event\player\nick;

use pocketmine\event\player\PlayerEvent;
use ryzerbe\core\player\RyZerPlayer;

class PlayerUnnickEvent extends PlayerEvent {

    private string $oldNickName;

    private RyZerPlayer $ryZerPlayer;

    public function __construct(RyZerPlayer $player, string $oldNickName){
        $this->player = $player->getPlayer();
        $this->ryZerPlayer = $player;
        $this->oldNickName = $oldNickName;
    }

    /**
     * @return RyZerPlayer
     */
    public function getRyZerPlayer(): RyZerPlayer{
        return $this->ryZerPlayer;
    }

    /**
     * @return string
     */
    public function getOldNickName(): string{
        return $this->oldNickName;
    }
}