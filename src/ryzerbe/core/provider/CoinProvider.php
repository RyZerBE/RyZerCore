<?php

namespace ryzerbe\core\provider;

use mysqli;
use pocketmine\Server;
use ryzerbe\core\event\coin\PlayerCoinsAddEvent;
use ryzerbe\core\event\coin\PlayerCoinsRemoveEvent;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;

class CoinProvider {

    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function addCoins(string $playerName, int $coins){
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($playerName, $coins){
            $mysqli->query("UPDATE Coins SET coins=coins+'$coins' WHERE playername='$playerName'");
        }, function(Server $server, $result) use ($playerName, $coins){
            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($playerName);
            if($ryzerPlayer === null) return;

            $ryzerPlayer->addCoins($coins);
            $ryzerPlayer->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('added-coins', $ryzerPlayer->getPlayer()->getName(), ['#coins' => $coins." Coins"]));
            $ev = new PlayerCoinsAddEvent($ryzerPlayer->getPlayer(), $coins);
            $ev->call();
        });
    }

    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function removeCoins(string $playerName, int $coins){
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($playerName, $coins){
            $mysqli->query("UPDATE Coins SET coins=coins-'$coins' WHERE playername='$playerName'");
        }, function(Server $server, $result) use ($playerName, $coins){
            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($playerName);
            if($ryzerPlayer === null) return;

            $ryzerPlayer->removeCoins($coins);
            $ryzerPlayer->getPlayer()->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('removed-coins', $ryzerPlayer->getPlayer()->getName(), ['#coins' => $coins." Coins"]));
            $ev = new PlayerCoinsRemoveEvent($ryzerPlayer->getPlayer(), $coins);
            $ev->call();
        });
    }

    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function setCoins(string $playerName, int $coins){
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(mysqli $mysqli) use ($playerName, $coins){
            $mysqli->query("UPDATE Coins SET coins='$coins' WHERE playername='$playerName'");
        }, function(Server $server, $result) use ($playerName, $coins){
            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($playerName);
            if($ryzerPlayer === null) return;

            $ryzerPlayer->setCoins($coins);
            $ryzerPlayer->getPlayer()->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('set-coins', $ryzerPlayer->getPlayer()->getName(), ['#coins' => $coins." Coins"]));
        });
    }
}