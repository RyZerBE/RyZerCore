<?php

namespace ryzerbe\core\event\player\anticheat;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;
use ryzerbe\core\anticheat\AntiCheatPlayer;
use ryzerbe\core\anticheat\Check;
use ryzerbe\core\player\PMMPPlayer;

class PlayerAntiCheatKickEvent extends PlayerEvent implements Cancellable {

    public function __construct(protected AntiCheatPlayer $cheatPlayer, protected Check $module){
        $this->player = $cheatPlayer->getPlayer();
    }

    /**
     * @return AntiCheatPlayer
     */
    public function getAntiCheatPlayer(): AntiCheatPlayer{
        return $this->cheatPlayer;
    }

    /**
     * @return Player|PMMPPlayer
     */
    public function getPlayer(): PMMPPlayer|Player{
        return $this->player;
    }

    /**
     * @return Check
     */
    public function getModule(): Check{
        return $this->module;
    }
}