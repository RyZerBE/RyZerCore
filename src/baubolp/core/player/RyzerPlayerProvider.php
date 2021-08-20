<?php


namespace baubolp\core\player;


use baubolp\core\listener\own\PlayerRegisterEvent;
use pocketmine\Player;

class RyzerPlayerProvider
{
    /** @var RyzerPlayer[] */
    private static array $players = [];
    /** @var LoginPlayerData[] */
    public static array $loginData = [];


    /**
     * @param Player $player
     */
    public static function registerRyzerPlayer(Player $player)
    {
        self::$players[$player->getName()] = new RyzerPlayer($player, RyzerPlayerProvider::$loginData[$player->getName()]);

        $ev = new PlayerRegisterEvent($player, RyzerPlayerProvider::$loginData[$player->getName()], RyzerPlayerProvider::getRyzerPlayer($player->getName()));
        $ev->call();
    }

    /**
     * @return RyzerPlayer[]
     */
    public static function getRyzerPlayers(): array
    {
        return self::$players;
    }

    /**
     * @param string $player
     * @return RyzerPlayer|null
     */
    public static function getRyzerPlayer(string $player): ?RyzerPlayer
    {
        if(array_key_exists($player, self::$players)) return self::$players[$player];

        return null;
    }

    /**
     * @param string $player
     */
    public static function unregisterRyzerPlayer(string $player)
    {
        unset(self::$players[$player]);
    }
}