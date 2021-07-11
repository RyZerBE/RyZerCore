<?php


namespace baubolp\core\listener\own;


use baubolp\core\player\LoginPlayerData;
use baubolp\core\player\RyzerPlayer;
use pocketmine\event\Event;

class RyZerPlayerAuthEvent extends Event
{
    /** @var RyzerPlayer */
    private $ryZerPlayer;
    /** @var LoginPlayerData */
    private $loginPlayerData;

    public function __construct(RyzerPlayer $player, LoginPlayerData $data)
    {
        $this->ryZerPlayer = $player;
        $this->loginPlayerData = $data;
    }

    /**
     * @return \baubolp\core\player\RyzerPlayer
     */
    public function getRyZerPlayer(): RyzerPlayer
    {
        return $this->ryZerPlayer;
    }

    /**
     * @return \baubolp\core\player\LoginPlayerData
     */
    public function getLoginPlayerData(): LoginPlayerData
    {
        return $this->loginPlayerData;
    }
}