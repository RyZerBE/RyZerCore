<?php

namespace ryzerbe\core\player\networklevel;

use baubolp\ryzerbe\lobbycore\player\LobbyPlayerCache;
use baubolp\ryzerbe\lobbycore\provider\LottoProvider;
use Closure;
use mysqli;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\networklevel\reward\CoinReward;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\provider\CoinProvider;
use ryzerbe\core\util\async\AsyncExecutor;

class NetworkLevelProvider {
    /** @var LevelUpReward[]  */
    public static array $rewards = [];

    public static function initRewards(): void{
        self::registerRewards([
            new LevelUpReward(2, "3000 Startcoins", function(int $level, RyZerPlayer $ryzerPlayer): void{
                CoinProvider::addCoins($ryzerPlayer->getPlayer()->getName(), 3000);
            }),
            new LevelUpReward(4, "2 Lottotickets", function(int $level, RyZerPlayer $ryzerPlayer): void{
                LottoProvider::addTicket(LobbyPlayerCache::getLobbyPlayer($ryzerPlayer->getPlayer()), 2);
            }),
            new LevelUpReward(15, TextFormat::GOLD."Custom Tag", function(int $level, RyZerPlayer $ryZerPlayer): void{
                $ryZerPlayer->addPlayerPermission("lobby.status");
            })
        ]);

        $perLevelCoin = function (int $level) {
            return match (true) {
                $level <= 15 => 500,
                $level <= 50 => 250,
                $level <= 100 => 150,
            };
        };

        for($i = 3; $i < 15; $i++) {
            if(self::getReward($i) !== null) continue;
            self::registerReward(new CoinReward($i, $perLevelCoin($i) * $i));
        }
        for ($i = 16; $i < 50; $i++) {
            if(self::getReward($i) !== null) continue;
            self::registerReward(new CoinReward($i, 6000 + ($perLevelCoin($i) * $i)));
        }
        for ($i = 51; $i < 100; $i++) {
            if(self::getReward($i) !== null) continue;
            self::registerReward(new CoinReward($i, 6000 + ($perLevelCoin($i) * $i)));
        }
    }

    public static function registerReward(LevelUpReward $reward){
        self::$rewards[$reward->getLevel()] = $reward;
    }

    /**
     * @param LevelUpReward[] $rewards
     */
    public static function registerRewards(array $rewards){
        foreach($rewards as $reward) self::$rewards[$reward->getLevel()] = $reward;
    }

    public static function getReward(int $level): ?LevelUpReward{
        return self::$rewards[$level] ?? null;
    }

    /**
     * @return LevelUpReward[]
     */
    public static function getRewards(): array{
        return self::$rewards;
    }

    public static function addLevelProgress(string $playerName, int $progress, int $progress_today, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $progress, $progress_today): void {
            $mysqli->query("UPDATE networklevel SET level_progress=level_progress+'$progress', level_progress_today='$progress_today', last_level_progress=CURRENT_TIMESTAMP WHERE playername='$playerName'");
        }, $closure);
    }

    public static function setLevelProgress(string $playerName, int $progress, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $progress): void {
            $mysqli->query("UPDATE networklevel SET level_progress='$progress' WHERE playername='$playerName'");
        }, $closure);
    }

    public static function addLevel(string $playerName, int $level = 1, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $level): void {
            $mysqli->query("UPDATE networklevel SET level=level+'$level' WHERE playername='$playerName'");
        }, $closure);
    }

    public static function setLevel(string $playerName, int $level, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $level): void {
            $mysqli->query("UPDATE networklevel SET level='$level' WHERE playername='$playerName'");
        }, $closure);
    }
}