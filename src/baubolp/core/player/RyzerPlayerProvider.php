<?php


namespace baubolp\core\player;


use baubolp\core\listener\own\PlayerRegisterEvent;
use baubolp\core\task\LoadAsyncDataTask;
use pocketmine\Player;
use pocketmine\Server;

class RyzerPlayerProvider
{
    /** @var \baubolp\core\player\RyzerPlayer[]  */
    private static $players = [];
    /** @var \baubolp\core\player\LoginPlayerData[]  */
    public static $loginData = [];


    /**
     * @param \pocketmine\Player $player
     */
    public static function registerRyzerPlayer(Player $player)
    {
        self::$players[$player->getName()] = new RyzerPlayer($player, RyzerPlayerProvider::$loginData[$player->getName()]);

        $ev = new PlayerRegisterEvent($player, RyzerPlayerProvider::$loginData[$player->getName()], RyzerPlayerProvider::getRyzerPlayer($player->getName()));
        $ev->call();
    }

    /**
     * @return \baubolp\core\player\RyzerPlayer[]
     */
    public static function getRyzerPlayers(): array
    {
        return self::$players;
    }

    /**
     * @param string $player
     * @return \baubolp\core\player\RyzerPlayer|null
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