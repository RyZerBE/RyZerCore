<?php

namespace ryzerbe\core\provider;

use pocketmine\event\Listener;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\RyZerBE;
use function count;
use const PHP_INT_MAX;

class VIPJoinProvider implements RyZerProvider, Listener {

    /** @var bool  */
    public static bool $enabled = false;
    /** @var int  */
    public static int $maxPlayers = PHP_INT_MAX;

    /**
     * @param bool $enabled
     * @param int $maxPlayers
     */
    public static function setEnabled(bool $enabled, int $maxPlayers): void{
        self::$enabled = $enabled;
        self::$maxPlayers = $maxPlayers;
    }

    /**
     * @param int $maxPlayers
     */
    public static function enable(int $maxPlayers){
        self::$enabled = true;
        self::$maxPlayers = $maxPlayers;
    }

    public static function disable(){
        self::$enabled = false;
    }

    /**
     * @param RyZerPlayerAuthEvent $event
     */
    public function onAuth(RyZerPlayerAuthEvent $event){
        if(!self::$enabled || self::$maxPlayers === PHP_INT_MAX) return;
        $players = RyZerPlayerProvider::getRyzerPlayers();
        if(count($players) <= self::$maxPlayers) return;

        $joinedPlayer = $event->getRyZerPlayer();
        $checked = 0;
        foreach($players as $player){
            $checked++;
            if($player->getRank()->getJoinPower() >= $joinedPlayer->getRank()->getJoinPower()) continue;
            $player->getPlayer()->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("vip-kick", $player->getPlayer(), ["#rank" => $player->getRank()->getColor().$player->getRank()->getRankName()]));
            $player->sendToLobby();
            $joinedPlayer->getPlayer()->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("vip-kick-successful", $joinedPlayer->getPlayer()));
            break;
        }

        if($checked === count($players)) {
            $joinedPlayer->getPlayer()->sendMessage(LanguageProvider::getMessageContainer("vip-kick-failed", $joinedPlayer->getPlayer()));
            $joinedPlayer->sendToLobby();
        }
    }
}