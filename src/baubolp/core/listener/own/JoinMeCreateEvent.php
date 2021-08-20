<?php


namespace baubolp\core\listener\own;


use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\Player;

class JoinMeCreateEvent extends Event implements Cancellable
{

    private Player $player;
    /** @var string */
    private string $reason;

    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @param string $reason
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}