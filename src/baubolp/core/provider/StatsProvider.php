<?php


namespace baubolp\core\provider;


use baubolp\core\Ryzer;

class StatsProvider
{
    /**
     * @param string $game
     */
    public static function createGameTable(string $game)
    {
        Ryzer::getAsyncConnection()->execute("CREATE TABLE IF NOT EXISTS $game(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(32) NOT NULL, kills integer(16) NOT NULL, deaths integer(16) NOT NULL, wins integer(16) NOT NULL, loses integer(16) NOT NULL, rounds integer(16) NOT NULL, elo integer(64) NOT NULL)", 'Statistics', null);
    }

    /**
     * @param string $playerName
     * @param string $game
     * @param int $kills
     */
    public static function addKills(string $playerName, string $game, int $kills)
    {
        Ryzer::getAsyncConnection()->execute("UPDATE $game SET kills=kills+'$kills' WHERE playername='$playerName'", 'Statistics', null);
    }

    /**
     * @param string $playerName
     * @param string $game
     * @param int $rounds
     */
    public static function addRound(string $playerName, string $game, int $rounds = 1)
    {
        Ryzer::getAsyncConnection()->execute("UPDATE $game SET rounds=rounds+'$rounds' WHERE playername='$playerName'", 'Statistics', null);
    }

    /**
     * @param string $playerName
     * @param string $game
     * @param int $deaths
     */
    public static function addDeaths(string $playerName, string $game, int $deaths)
    {
        Ryzer::getAsyncConnection()->execute("UPDATE $game SET deaths=deaths+'$deaths' WHERE playername='$playerName'", 'Statistics', null);
    }

    /**
     * @param string $playerName
     * @param string $game
     * @param int $win
     */
    public static function addWin(string $playerName, string $game, int $win = 1)
    {
        Ryzer::getAsyncConnection()->execute("UPDATE $game SET wins=wins+'$win' WHERE playername='$playerName'", 'Statistics', null);
    }

    /**
     * @param string $playerName
     * @param string $game
     * @param int $lose
     */
    public static function addLose(string $playerName, string $game, int $lose = 1)
    {
        Ryzer::getAsyncConnection()->execute("UPDATE $game SET loses=loses+'$lose' WHERE playername='$playerName'", 'Statistics', null);
    }

    /**
     * @param string $playerName
     * @param string $game
     * @param int $elo
     */
    public static function addElo(string $playerName, string $game, int $elo)
    {
        Ryzer::getAsyncConnection()->execute("UPDATE $game SET elo=elo+'$elo' WHERE playername='$playerName'", 'Statistics', null);
    }

    /**
     * @param string $playerName
     * @param string $game
     * @param int $elo
     */
    public static function removeElo(string $playerName, string $game, int $elo)
    {
        Ryzer::getAsyncConnection()->execute("UPDATE $game SET elo=elo-'$elo' WHERE playername='$playerName'", 'Statistics', null);
    }
}