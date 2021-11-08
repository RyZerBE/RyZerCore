<?php

namespace ryzerbe\core\rank;

use mysqli;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\util\async\AsyncExecutor;

class RankManager {
    use SingletonTrait;
    /** @var Rank  */
    public Rank $backupRank;

    /** @var Rank[] */
    public array $ranks = [];

    public function __construct(){
        $this->backupRank = new Rank("Player", "§f{player_name}", "§fS §8× §7{player_name} §8» §7{MSG}", "§f", 0, []);
    }

    /**
     * @return Rank
     */
    public function getBackupRank(): Rank{
        return $this->backupRank;
    }

    /**
     * @return Rank[]
     */
    public function getRanks(): array{
        return $this->ranks;
    }

    /**
     * @param Rank $rank
     */
    public function addRank(Rank $rank){
        $this->ranks[$rank->getRankName()] = $rank;
    }

    /**
     * @param string $rankName
     */
    public function createRank(string $rankName): void{
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($rankName){
            $mysqli->query("INSERT INTO `ranks`(`rankname`, `nametag`, `chatprefix`, `color`, `joinpower`, `permissions`) VALUES ('$rankName', '§f{player_name}', '§f$rankName §8× §7{player_name} §8» §7{MSG}', '§f', '0', '')");
        });
    }

    public function fetchRanks(){
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli){
            $result = $mysqli->query("SELECT * FROM ranks");
            $ranks = [];
            if($result->num_rows > 0){
                while($data = $result->fetch_assoc()){
                    $ranks[$data['rankname']] = ['nametag' => $data['nametag'], 'chatprefix' => $data['chatprefix'], 'permissions' => explode(":", $data['permissions']), 'joinPower' => $data['joinpower'], "color" => $data["color"]];
                }
            }

            return $ranks;
        }, function(Server $server, array $rankResult){
            foreach($rankResult as $rankName => $data){
                $rank = new Rank($rankName, $data["nametag"], $data["chatprefix"], $data["color"], $data["joinPower"], $data["permissions"]);
                RankManager::getInstance()->addRank($rank);
            }
        });
    }

    /**
     * @param string $rankName
     * @return Rank|null
     */
    public function getRank(string $rankName): ?Rank{
        return $this->ranks[$rankName] ?? null;
    }
}