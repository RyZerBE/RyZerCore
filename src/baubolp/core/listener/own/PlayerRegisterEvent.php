<?php


namespace baubolp\core\listener\own;


use baubolp\core\player\LoginPlayerData;
use baubolp\core\player\RyzerPlayer;
use baubolp\core\player\RyzerPlayerProvider;
use pocketmine\event\Event;
use pocketmine\Player;

class PlayerRegisterEvent extends Event
{
    /** @var \pocketmine\Player  */
    private $player;
    /** @var \baubolp\core\player\RyzerPlayer  */
    private $ryzerPlayer;
    /** @var \baubolp\core\player\LoginPlayerData  */
    private $loginData;

    public function __construct(Player $player, LoginPlayerData $loginPlayerData, RyzerPlayer $ryzerPlayer)
    {
        $this->player = $player;
        $this->ryzerPlayer = $ryzerPlayer;
        $this->loginData = $loginPlayerData;
    }

    /**
     * @return \baubolp\core\player\LoginPlayerData
     */
    public function getLoginData(): \baubolp\core\player\LoginPlayerData
    {
        return $this->loginData;
    }

    /**
     * @return \pocketmine\Player
     */
    public function getPlayer(): \pocketmine\Player
    {
        return $this->player;
    }

    /**
     * @return \baubolp\core\player\RyzerPlayer
     */
    public function getRyzerPlayer(): \baubolp\core\player\RyzerPlayer
    {
        return $this->ryzerPlayer;
    }
}