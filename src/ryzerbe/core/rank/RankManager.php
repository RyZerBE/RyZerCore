<?php

namespace ryzerbe\core\rank;

use mysqli;
use pocketmine\permission\PermissionManager;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use ryzerbe\core\util\async\AsyncExecutor;
use function explode;
use function str_replace;

class RankManager {
    use SingletonTrait;

    public Rank $backupRank;

    /** @var Rank[] */
    public array $ranks = [];

    public function __construct(){
        $this->backupRank = new Rank("Player", "§f{player_name}", "§fS §8× §7{player_name} §8» §8{MSG}", "§f", 0, []);
    }

    public function getBackupRank(): Rank{
        return $this->backupRank;
    }

    /**
     * @return Rank[]
     */
    public function getRanks(): array{
        return $this->ranks;
    }

    public function addRank(Rank $rank){
        $this->ranks[$rank->getRankName()] = $rank;
    }

    public function createRank(string $rankName, string $nameTag, string $chatPrefix, string $color, int $joinPower): void{
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($rankName, $joinPower, $chatPrefix, $nameTag, $color){ //&f$rankName &8× &7{player_name} &8» &7{MSG}
            $mysqli->query("INSERT INTO `ranks`(`rankname`, `nametag`, `chatprefix`, `color`, `joinpower`, `permissions`) VALUES ('$rankName', '$nameTag', '$chatPrefix', '$color', '$joinPower', '') ON DUPLICATE KEY UPDATE nametag='$nameTag',chatprefix='$chatPrefix',color='$color',joinpower='$joinPower'");
        }, function(Server $server, $result) use ($rankName, $chatPrefix, $nameTag, $joinPower, $color): void{
            $rank = RankManager::getInstance()->getRank($rankName);
            if($rank !== null) {
                $rank->setJoinPower($joinPower);
                $rank->setNameTag(str_replace("&", TextFormat::ESCAPE, $nameTag));
                $rank->setChatPrefix(str_replace("&", TextFormat::ESCAPE, $chatPrefix));
                $rank->setColor(str_replace("&", TextFormat::ESCAPE, $color));
                return;
            }
            $rank = new Rank($rankName, str_replace("&", TextFormat::ESCAPE, $nameTag), str_replace("&", TextFormat::ESCAPE, $chatPrefix), str_replace("&", TextFormat::ESCAPE, $color), $joinPower, []);
            RankManager::getInstance()->addRank($rank);
        });
    }

    public function fetchRanks(){
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli){
            $result = $mysqli->query("SELECT * FROM ranks");
            $ranks = [];
            if($result->num_rows > 0){
                while($data = $result->fetch_assoc()){
                    $ranks[$data['rankname']] = ['nametag' => $data['nametag'], 'chatprefix' => $data['chatprefix'], 'permissions' => $data['permissions'], 'joinPower' => $data['joinpower'], "color" => $data["color"]];
                }
            }

            return $ranks;
        }, function(Server $server, array $rankResult){
            foreach($rankResult as $rankName => $data){
                $rank = new Rank($rankName, str_replace("&", TextFormat::ESCAPE, $data["nametag"]), str_replace("&", TextFormat::ESCAPE, $data["chatprefix"]), str_replace("&", TextFormat::ESCAPE, $data["color"]), $data["joinPower"], explode(":", $data["permissions"]));
                RankManager::getInstance()->addRank($rank);
            }
        });
    }

    public function getRank(string $rankName): ?Rank{
        return $this->ranks[$rankName] ?? null;
    }

    public function setRank(string $playerName, Rank $rank){
        $rankName = $rank->getRankName();
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use($rank, $playerName, $rankName): void{
            $mysqli->query("INSERT INTO `playerranks`(`player`, `rankname`, `permissions`) VALUES ('$playerName', '$rankName', '') ON DUPLICATE KEY UPDATE rankname='$rankName'");
        });
    }

    public function convertPermFormat(array $permissions): array{
        $perms = [];
        foreach ($permissions as $perm) {
            if ($perm == "*") {
                foreach(PermissionManager::getInstance()->getPermissions() as $permission) {
                    $perms[$permission->getName()] = true;
                }
            } else {
                $perms[$perm] = true;
            }
        }
        return $perms;
    }
}