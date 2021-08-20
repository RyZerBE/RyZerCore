<?php

namespace baubolp\core\provider;

use Closure;
use mysqli;

class NetworkLevelProvider {

    /**
     * @param string $playerName
     * @param int $progress
     * @param Closure|null $closure
     */
    public static function addLevelProgress(string $playerName, int $progress, ?Closure $closure = null): void {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($playerName, $progress): void {
            $mysqli->query("UPDATE NetworkLevel SET level_progress=level_progress+'$progress' WHERE playername='$playerName'");
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