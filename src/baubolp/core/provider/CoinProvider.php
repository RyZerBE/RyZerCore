<?php


namespace baubolp\core\provider;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use mysqli;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class CoinProvider
{
    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function addCoins(string $playerName, int $coins)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($playerName, $coins){
            $mysqli->query("UPDATE Coins SET coins=coins+'$coins' WHERE playername='$playerName'");
        }, function (Server $server, $result) use ($playerName, $coins){
            if(($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
                $obj->setCoins($obj->getCoins() + $coins);
                $obj->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('added-coins', $obj->getPlayer()->getName(), ['#coins' => $coins." Coins"]));
            }
        });
    }

    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function removeCoins(string $playerName, int $coins)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($playerName, $coins){
            $mysqli->query("UPDATE Coins SET coins=coins-'$coins' WHERE playername='$playerName'");
        }, function (Server $server, $result) use ($playerName, $coins){
            if(($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
                $obj->setCoins($obj->getCoins() - $coins);
                $obj->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('removed-coins', $obj->getPlayer()->getName(), ['#coins' => $coins." Coins"]));
            }
        });
    }

    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function setCoins(string $playerName, int $coins)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($playerName, $coins){
            $mysqli->query("UPDATE Coins SET coins='$coins' WHERE playername='$playerName'");
        }, function (Server $server, $result) use ($playerName, $coins){
            if(($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
                $obj->setCoins($coins);
                $obj->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('set-coins', $obj->getPlayer()->getName(), ['#coins' => $coins." Coins"]));
            }
        });
    }
}