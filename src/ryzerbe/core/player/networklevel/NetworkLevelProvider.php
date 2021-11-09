<?php

namespace ryzerbe\core\player\networklevel;

use Closure;
use mysqli;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\provider\CoinProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\LevelUpReward;

class NetworkLevelProvider {

    /** @var LevelUpReward[]  */
    public static array $rewards = [];

    public static function initRewards(): void{
        self::registerRewards([
            new LevelUpReward(2, "2000 Coins", function(int $level, RyZerPlayer $ryzerPlayer): void{
                CoinProvider::addCoins($ryzerPlayer->getPlayer()->getName(), 2000);
            })
        ]);
    }

    /**
     * @param LevelUpReward $reward
     */
    public static function registerReward(LevelUpReward $reward){
        self::$rewards[$reward->getLevel()] = $reward;
    }

    /**
     * @param LevelUpReward[] $rewards
     */
    public static function registerRewards(array $rewards){
        foreach($rewards as $reward) self::$rewards[$reward->getLevel()] = $reward;
    }

    /**
     * @param int $level
     * @return LevelUpReward|null
     */
    public static function getReward(int $level): ?LevelUpReward{
        return self::$rewards[$level] ?? null;
    }

    /**
     * @return LevelUpReward[]
     */
    public static function getRewards(): array{
        return self::$rewards;
    }

    /**
     * @param string $playerName
     * @param int $progress
     * @param int $progress_today
     * @param Closure|null $closure
     */
    public static function addLevelProgress(string $playerName, int $progress, int $progress_today, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $progress, $progress_today): void {
            $mysqli->query("UPDATE networklevel SET level_progress=level_progress+'$progress', level_progress_today='$progress_today', last_level_progress=CURRENT_TIMESTAMP WHERE playername='$playerName'");
        }, $closure);
    }

    /**
     * @param string $playerName
     * @param int $progress
     * @param Closure|null $closure
     */
    public static function setLevelProgress(string $playerName, int $progress, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $progress): void {
            $mysqli->query("UPDATE networklevel SET level_progress='$progress' WHERE playername='$playerName'");
        }, $closure);
    }

    /**
     * @param string $playerName
     * @param int $level
     * @param Closure|null $closure
     */
    public static function addLevel(string $playerName, int $level = 1, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $level): void {
            $mysqli->query("UPDATE networklevel SET level=level+'$level' WHERE playername='$playerName'");
        }, $closure);
    }

    /**
     * @param string $playerName
     * @param int $level
     * @param Closure|null $closure
     */
    public static function setLevel(string $playerName, int $level, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $level): void {
            $mysqli->query("UPDATE networklevel SET level='$level' WHERE playername='$playerName'");
        }, $closure);
    }
}