<?php


namespace baubolp\core\listener\own;


use baubolp\core\player\LoginPlayerData;
use baubolp\core\player\RyzerPlayer;
use pocketmine\event\Event;
use pocketmine\Player;

class PlayerRegisterEvent extends Event
{
    /** @var Player */
    private Player $player;
    /** @var RyzerPlayer */
    private RyzerPlayer $ryzerPlayer;
    /** @var LoginPlayerData */
    private LoginPlayerData $loginData;

    public function __construct(Player $player, LoginPlayerData $loginPlayerData, RyzerPlayer $ryzerPlayer)
    {
        $this->player = $player;
        $this->ryzerPlayer = $ryzerPlayer;
        $this->loginData = $loginPlayerData;
    }

    /**
     * @return LoginPlayerData
     */
    public function getLoginData(): LoginPlayerData
    {
        return $this->loginData;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return RyzerPlayer
     */
    public function getRyzerPlayer(): RyzerPlayer
    {
        return $this->ryzerPlayer;
    }
}