<?php

namespace ryzerbe\core\player;

use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\core\player\networklevel\NetworkLevel;
use ryzerbe\core\provider\CoinProvider;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\util\async\AsyncExecutor;

class RyZerPlayer {

    /** @var LoginPlayerData  */
    private LoginPlayerData $loginPlayerData;
    /** @var Player  */
    private Player $player;
    /** @var NetworkLevel|null  */
    private ?NetworkLevel $networkLevel;
    /** @var string  */
    private string $languageName = "English";
    /** @var int  */
    private int $coins = 0;
    /** @var Rank  */
    private Rank $rank;

    /**
     * @param Player $player
     * @param LoginPlayerData $playerData
     */
    public function __construct(Player $player, LoginPlayerData $playerData){
        $this->player = $player;
        $this->loginPlayerData = $playerData;
        $this->rank = RankManager::getInstance()->getBackupRank();
        $this->loadData();
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player{
        return $this->player;
    }

    /**
     * @return Rank
     */
    public function getRank(): Rank{
        return $this->rank;
    }

    /**
     * @param Rank $rank
     */
    public function setRank(Rank $rank): void{
        $this->rank = $rank;
    }

    /**
     * @param int $coins
     * @param bool $mysql
     */
    public function addCoins(int $coins, bool $mysql = false){
        $this->coins += $coins;
        if($mysql) CoinProvider::addCoins($this->getPlayer()->getName(), $coins);
    }

    /**
     * @param int $coins
     * @param bool $mysql
     */
    public function removeCoins(int $coins, bool $mysql = false){
        $this->coins -= $coins;
        if($mysql) CoinProvider::removeCoins($this->getPlayer()->getName(), $coins);
    }
    /**
     * @param int $coins
     * @param bool $mysql
     */
    public function setCoins(int $coins, bool $mysql = false){
        $this->coins = $coins;
        if($mysql) CoinProvider::setCoins($this->getPlayer()->getName(), $coins);
    }

    /**
     * @return int
     */
    public function getCoins(): int{
        return $this->coins;
    }

    /**
     * @return LoginPlayerData
     */
    public function getLoginPlayerData(): LoginPlayerData{
        return $this->loginPlayerData;
    }

    /**
     * @return NetworkLevel|null
     */
    public function getNetworkLevel(): ?NetworkLevel{
        return $this->networkLevel;
    }

    /**
     * @param NetworkLevel|null $networkLevel
     */
    public function setNetworkLevel(?NetworkLevel $networkLevel): void{
        $this->networkLevel = $networkLevel;
    }

    public function loadData(): void{
        $playerName = $this->getPlayer()->getName();
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName): array{
            $playerData = [];
            $res = $mysqli->query("SELECT * FROM playerlanguage WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["language"] = $res->fetch_assoc()["language"] ?? "English";
            }else{
                $mysqli->query("INSERT INTO `playerlanguage`(`player`) VALUES ('$playerName')");
                $playerData["language"] = null;
            }

            $res = $mysqli->query("SELECT * FROM coins WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["coins"] = $res->fetch_assoc()["coins"] ?? 0;
            }else{
                $mysqli->query("INSERT INTO `coins`(`player`) VALUES ('$playerName')");
                $playerData["coins"] = 0;
            }

            $res = $mysqli->query("SELECT * FROM playerranks WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["rank"] = $res->fetch_assoc()["rank"] ?? "Player";
                $playerData["permissions"] = $res->fetch_assoc()["permissions"] ?? "";
            }else{
                $mysqli->query("INSERT INTO `playerranks`(`player`) VALUES ('$playerName')");
            }

            return $playerData;
        }, function(Server $server, array $playerData) use ($playerName): void{
            $player = $server->getPlayer($playerName);
            if($player === null) return;

            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
            if($ryzerPlayer === null) return;

            $ryzerPlayer->setCoins($playerData["coins"]);
            if($playerData["language"] === null) {
                $player->getServer()->dispatchCommand($player, "lang");
            }else {
                $ryzerPlayer->setLanguage($playerData["language"] ?? "English");
            }

            $rank = RankManager::getInstance()->getRank($playerData["rank"] ?? "Player");
            if($rank === null) $rank = RankManager::getInstance()->getBackupRank();
            $player->

            $ev = new RyZerPlayerAuthEvent($ryzerPlayer);
            $ev->call();
        });
    }

    /**
     * @param string $languageName
     * @param bool $mysql
     */
    public function setLanguage(string $languageName, bool $mysql = false): void{
        $this->languageName = $languageName;
        if($mysql) {
            $playerName = $this->getPlayer()->getName();
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($languageName, $playerName): void{
                $mysqli->query("UPDATE playerlanguage SET language='$languageName' WHERE player='$playerName'");
            });
        }
    }

    /**
     * @return string
     */
    public function getLanguageName(): string{
        return $this->languageName;
    }
}