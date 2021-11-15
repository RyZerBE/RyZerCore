<?php

namespace ryzerbe\core\provider;

use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayer;

class NickProvider implements RyZerProvider {
    /**
     * @param string $nickName
     * @param bool $ryZerPlayer
     * @return Player|RyZerPlayer|null
     */
    public static function getPlayerByNick(string $nickName, bool $ryZerPlayer): null|Player|RyZerPlayer{
        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if(!$onlinePlayer instanceof PMMPPlayer) continue;
            $rbePlayer = $onlinePlayer->getRyZerPlayer();
            if($rbePlayer === null) continue;

            if($rbePlayer->getName(true) === $nickName) {
                if($ryZerPlayer) return $rbePlayer; else return $onlinePlayer;
            }
        }

        return null;
    }
}