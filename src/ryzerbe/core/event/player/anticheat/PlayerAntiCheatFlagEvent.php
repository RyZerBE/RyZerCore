<?php

namespace ryzerbe\core\event\player\anticheat;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use ryzerbe\core\anticheat\AntiCheatPlayer;
use ryzerbe\core\anticheat\Check;

class PlayerAntiCheatFlagEvent extends PlayerEvent implements Cancellable {

    public function __construct(private AntiCheatPlayer $cheatPlayer, private Check $checkModule, private string $reason){
        $this->player = $this->cheatPlayer->getPlayer();
    }

    /**
     * @return AntiCheatPlayer
     */
    public function getCheatPlayer(): AntiCheatPlayer{
        return $this->cheatPlayer;
    }

    /**
     * @return Check
     */
    public function getCheckModule(): Check{
        return $this->checkModule;
    }

    /**
     * @return string
     */
    public function getReason(): string{
        return $this->reason;
    }
}