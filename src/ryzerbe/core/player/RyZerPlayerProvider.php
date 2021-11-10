<?php

namespace ryzerbe\core\player;

use pocketmine\Player;
use ryzerbe\core\player\data\LoginPlayerData;

class RyZerPlayerProvider {

    /** @var RyzerPlayer[] */
    private static array $players = [];
    /** @var LoginPlayerData[] */
    public static array $loginData = [];


    /**
     * @param Player $player
     */
    public static function registerRyzerPlayer(Player $player)
    {
        $rbePlayer = new RyZerPlayer($player, self::$loginData[$player->getName()]);
        self::$players[$player->getName()] = $rbePlayer;
        $rbePlayer->loadData();
    }

    /**
     * @return RyzerPlayer[]
     */
    public static function getRyzerPlayers(): array
    {
        return self::$players;
    }

    /**
     * @param Player|string $player
     * @return RyzerPlayer|null
     */
    public static function getRyzerPlayer(Player|string $player): ?RyZerPlayer
    {
        if($player instanceof Player) $player = $player->getName();

        return self::$players[$player] ?? null;
    }

    /**
     * @param string $player
     */
    public static function unregisterRyzerPlayer(string $player)
    {
        unset(self::$players[$player]);
    }
}