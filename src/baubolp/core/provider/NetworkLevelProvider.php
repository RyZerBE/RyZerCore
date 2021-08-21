<?php

namespace baubolp\core\provider;

use Closure;
use mysqli;
use function ceil;
use function strtotime;
use function time;

class NetworkLevelProvider {

    /**
     * @param string $playerName
     * @param int $progress
     * @param int $progress_today
     * @param Closure|null $closure
     */
    public static function addLevelProgress(string $playerName, int $progress, int $progress_today, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($playerName, $progress, $progress_today): void {
            $query = $mysqli->query("SELECT last_level_progress FROM NetworkLevel WHERE playername='$playerName'");
            $assoc = $query->fetch_assoc();
            $time = strtotime($assoc["last_level_progress"]);
            if(ceil($time / 86400) !== ceil(time() / 86400)) $progress_today = 0;

            $mysqli->query("UPDATE NetworkLevel SET level_progress=level_progress+'$progress', level_progress_today='$progress_today', last_level_progress=CURRENT_TIMESTAMP WHERE playername='$playerName'");
        }, $closure);
    }

    /**
     * @param string $playerName
     * @param int $progress
     * @param Closure|null $closure
     */
    public static function setLevelProgress(string $playerName, int $progress, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($playerName, $progress): void {
            $mysqli->query("UPDATE NetworkLevel SET level_progress='$progress' WHERE playername='$playerName'");
        }, $closure);
    }

    /**
     * @param string $playerName
     * @param int $level
     * @param Closure|null $closure
     */
    public static function addLevel(string $playerName, int $level = 1, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($playerName, $level): void {
            $mysqli->query("UPDATE NetworkLevel SET level=level+'$level' WHERE playername='$playerName'");
        }, $closure);
    }

    /**
     * @param string $playerName
     * @param int $level
     * @param Closure|null $closure
     */
    public static function setLevel(string $playerName, int $level, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($playerName, $level): void {
            $mysqli->query("UPDATE NetworkLevel SET level='$level' WHERE playername='$playerName'");
        }, $closure);
    }
}