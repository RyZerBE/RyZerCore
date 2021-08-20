<?php


namespace baubolp\core\listener\own;


use baubolp\core\player\LoginPlayerData;
use baubolp\core\player\RyzerPlayer;
use pocketmine\event\Event;

class RyZerPlayerAuthEvent extends Event
{
    /** @var RyzerPlayer */
    private RyzerPlayer $ryZerPlayer;
    /** @var LoginPlayerData */
    private LoginPlayerData $loginPlayerData;

    public function __construct(RyzerPlayer $player, LoginPlayerData $data)
    {
        $this->ryZerPlayer = $player;
        $this->loginPlayerData = $data;
    }

    /**
     * @return RyzerPlayer
     */
    public function getRyZerPlayer(): RyzerPlayer
    {
        return $this->ryZerPlayer;
    }

    /**
     * @return LoginPlayerData
     */
    public function getLoginPlayerData(): LoginPlayerData
    {
        return $this->loginPlayerData;
    }
}