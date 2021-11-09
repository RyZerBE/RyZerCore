<?php

namespace ryzerbe\core\player;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerDisconnectPacket;
use BauboLP\Cloud\Packets\PlayerMoveServerPacket;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\core\player\networklevel\NetworkLevel;
use ryzerbe\core\provider\CoinProvider;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\Clan;
use ryzerbe\core\util\Settings;
use ryzerbe\core\util\time\TimeAPI;

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
    /** @var int  */
    public int $gameTimeTicks = 0;

    /** @var Rank  */
    private Rank $rank;

    /** @var Clan|null  */
    private ?Clan $clan;

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
     * @param bool $pushPermissions
     * @param bool $mysql
     */
    public function setRank(Rank $rank, bool $pushPermissions = true, bool $mysql = false): void{
        $this->rank = $rank;

        if($pushPermissions) $this->getPlayer()->addAttachment(RyZerBE::getPlugin())->setPermissions($rank->getPermissionFormat());
        if($mysql) RankManager::getInstance()->setRank($this->getPlayer()->getName(), $rank);
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
        $mysqlData = Settings::$mysqlLoginData;
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $mysqlData): array{
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

            $res = $mysqli->query("SELECT * FROM gametime WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["ticks"] = $res->fetch_assoc()["ticks"] ?? 0;
            }else{
                $mysqli->query("INSERT INTO `gametime`(`player`) VALUES ('$playerName')");
            }

            $result = $mysqli->query("SELECT * FROM networklevel WHERE playername='$playerName'");
            if ($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    $playerData["network_level_progress"] = $data["level_progress"];
                    $playerData["network_level"] = $data["level"];
                    $playerData["level_progress_today"] = $data["level_progress_today"];
                    $playerData["last_level_progress"] = $data["last_level_progress"];
                }
            } else {
                $mysqli->query("INSERT INTO `networklevel`(`playername`) VALUES ('$playerName')");
                $playerData["network_level_progress"] = 0;
                $playerData["network_level"] = 1;
                $playerData["level_progress_today"] = 0;
                $playerData["last_level_progress"] = 0;
            }

            $clanDB = new mysqli($mysqlData["host"], $mysqlData["username"], $mysqlData["password"], "BetterClans");
            $result = $clanDB->query("SELECT * FROM ClanUsers WHERE playername='$playerName'");
            if($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    $playerData['clan'] = $data['clan_name'];
                }
            }else {
                $playerData['clan'] = null;
            }

            $clanName = $playerData['clan'];
            if($clanName != null && $clanName != "") {
                $result = $clanDB->query("SELECT * FROM Clans WHERE clan_name='$clanName'");
                if($result->num_rows > 0) {
                    while($data = $result->fetch_assoc()) {
                        $playerData['clanColor'] = $data['color'];
                        $playerData['clanTag'] = $data['clan_tag'];
                        $playerData['owner'] = $data['clan_owner'];
                        $playerData["clanElo"] = $data["elo"];
                    }
                }else {
                    $playerData['clanTag'] = "";
                    $playerData['clanColor'] = "§e";
                    $playerData["clanElo"] = 1000;
                }
            }else {
                $playerData['clanTag'] = "";
                $playerData['clanColor'] = "§e";
                $playerData["clanElo"] = 1000;
            }


            return $playerData;
        }, function(Server $server, array $playerData) use ($playerName): void{
            $player = $server->getPlayer($playerName);
            if($player === null) return;

            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
            if($ryzerPlayer === null) return;

            $ryzerPlayer->setCoins($playerData["coins"] ?? 0);
            $ryzerPlayer->gameTimeTicks = $playerData["ticks"] ?? 0;
            if($playerData["language"] === null) {
                $player->getServer()->dispatchCommand($player, "lang");
            }else {
                $ryzerPlayer->setLanguage($playerData["language"] ?? "English");
            }

            $rank = RankManager::getInstance()->getRank($playerData["rank"] ?? "Player");
            if($rank === null) $rank = RankManager::getInstance()->getBackupRank();
            $ryzerPlayer->setRank($rank);

            $ryzerPlayer->setNetworkLevel(new NetworkLevel($ryzerPlayer, $playerData["network_level"], $playerData["network_level_progress"], $playerData["level_progress_today"], strtotime($playerData["last_level_progress"])));

            if($playerData['clan'] != null && $playerData['clan'] != "null") {
                $ryzerPlayer->setClan(new Clan($playerData["clan"], $playerData["clanColor"].$playerData["clanTag"], (int)$playerData["clanElo"], $playerData["owner"]));
            }

            $ev = new RyZerPlayerAuthEvent($ryzerPlayer);
            $ev->call();
        });
    }

    public function saveData(): void{
        $gameTimeTicks = $this->getGameTimeTicks();
        $playerName = $this->getPlayer()->getName();

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($gameTimeTicks, $playerName): void{
            $mysqli->query("UPDATE gametime SET ticks='$$gameTimeTicks' WHERE player='$playerName'");
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

    /**
     * @return int
     */
    public function getGameTimeTicks(): int{
        return $this->gameTimeTicks;
    }

    /**
     * @return string
     */
    public function getOnlineTime(): string{
        return TimeAPI::convert($this->gameTimeTicks)->asShortString();
    }

    /**
     * @return Clan|null
     */
    public function getClan(): ?Clan{
        return $this->clan;
    }

    /**
     * @param Clan|null $clan
     */
    public function setClan(?Clan $clan): void{
        $this->clan = $clan;
    }

    /**
     * @param string $reason
     */
    public function kick(string $reason){
        $pk = new PlayerDisconnectPacket();
        $pk->addData("playerName", $this->getPlayer()->getName());
        $pk->addData("message", $reason);
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }

    /**
     * @param string $serverName
     */
    public function connectServer(string $serverName){
        $pk = new PlayerMoveServerPacket();
        $pk->addData("playerNames", $this->getPlayer()->getName());
        $pk->addData("serverName", $serverName);
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }
}