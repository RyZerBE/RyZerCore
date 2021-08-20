<?php


namespace baubolp\core\provider;


use baubolp\core\player\RyzerPlayer;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use baubolp\core\util\Rank;
use mysqli;
use pocketmine\permission\PermissionManager;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

class RankProvider
{
    /** @var Rank[]  */
    public static array $ranks = [];

    /**
     * @return Rank[]
     */
    public static function getRanks(): array
    {
        return self::$ranks;
    }

    /**
     * @param string $rank
     * @param int $joinpower
     */
    public static function createRank(string $rank, int $joinpower)
    {
        if(!self::existRank($rank))
        MySQLProvider::getSQLConnection("Core")->getSql()->query("INSERT INTO `Ranks`(`rankname`, `nametag`, `chatprefix`, `permissions`, `joinpower`) VALUES ('$rank', '&6$rank &7» &f{player_name}', '&6$rank &7» &f{player_name} &8» &7 {MSG}', '', '$joinpower')");
    }

    public static function loadRanks()
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli){
            $result = $mysqli->query("SELECT * FROM Ranks");
            $ranks = [];
            if($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    $ranks[$data['rankname']] = ['nametag' => $data['nametag'], 'chatprefix' => $data['chatprefix'], 'permissions' => explode(":", $data['permissions']), 'joinPower' => $data['joinpower']];
                }
            }

            return $ranks;
        }, function (Server $server, array $rankResult){
            foreach (array_keys($rankResult) as $key) {
                $data = $rankResult[$key];
                RankProvider::$ranks[$key] = new Rank($key, $data["nametag"], $data["chatprefix"], $data["permissions"], $data["joinPower"]);
            }
            MainLogger::getLogger()->info(count($rankResult)." Ranks were loaded!");
        });
    }

    /**
     * @param string $rank
     * @return bool
     */
    public static function existRank(string $rank)
    {
        return array_key_exists($rank, RankProvider::$ranks);
    }

    /**
     * @param string $rank
     * @return mixed
     */
    public static function getRankPermissions(string $rank)
    {
        return self::$ranks[$rank]->getPermissions();
    }

    /**
     * @param string $rank
     * @return mixed
     */
    public static function getRankJoinPower(string $rank)
    {
        return self::$ranks[$rank]->getJoinPower();
    }

    /**
     * @param string $rank
     * @return mixed
     */
    public static function getNameTag(string $rank)
    {
        return self::$ranks[$rank]->getNameTag();
    }

    /**
     * @param string $rank
     * @return mixed
     */
    public static function getChatPrefix(string $rank)
    {
        return self::$ranks[$rank]->getChatPrefix();
    }

    /**
     * @param string $rank
     * @param string $senderName
     * @param string $permission
     */
    public static function addPermToRank(string $rank, string $senderName, string $permission)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($senderName, $rank, $permission){
            $result = $mysqli->query("SELECT permissions From Ranks WHERE rankname='$rank'");
            $newPermissions = "";
            if($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    if($data['permissions'] == "") {
                        $newPermissions .= $this->permission;
                    }else {
                        $newPermissions .= $data['permissions'].":".$this->permission;
                    }
                }
                $mysqli->query("UPDATE Ranks SET permissions='$newPermissions' WHERE rankname='$rank'");
                return true;
            }
            return false;
        }, function (Server $server, bool $success) use ($permission, $senderName, $rank){
            if(($player = Server::getInstance()->getPlayerExact($senderName)) != null) {
                if($success)
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Die Permission ".TextFormat::AQUA.$permission.TextFormat::GRAY." wurde dem Rang ".TextFormat::AQUA.$rank.TextFormat::GREEN." hinzugefügt.");
                else
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :/");
            }
        });
    }

    /**
     * @param string $rank
     * @param string $senderName
     * @param string $permission
     */
    public static function removePermFromRank(string $rank, string $senderName, string $permission)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($rank, $senderName, $permission){
            $result = $mysqli->query("SELECT permissions From Ranks WHERE rankname='$rank'");
            $newPermissions = "";
            if($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    foreach (explode(":", $data['permissions']) as $permission) {
                        if($permission != $this->permission) {
                            if($newPermissions == "") {
                                $newPermissions = $this->permission;
                            }else {
                                $newPermissions = $newPermissions . ":".$this->permission;
                            }
                        }
                    }
                }
                $mysqli->query("UPDATE Ranks SET permissions='$newPermissions' WHERE rankname='$rank'");
                return true;
            }
            return false;
        }, function (Server $server, bool $success) use ($senderName, $permission, $rank){
            if(($player = Server::getInstance()->getPlayerExact($senderName)) != null) {
                if ($success)
                    $player->sendMessage(Ryzer::PREFIX . TextFormat::GRAY . "Die Permission " . TextFormat::AQUA . $permission . TextFormat::GRAY . " wurde dem Rang " . TextFormat::AQUA . $rank . TextFormat::RED . " entfernt.");
                else
                    $player->sendMessage(Ryzer::PREFIX . TextFormat::RED . "Etwas ist schief gelaufen :/");
            }
        });
    }

    /**
     * @param string $playerName
     * @param string $senderName
     * @param string $rank
     */
    public static function setRank(string $playerName, string $senderName, string $rank)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($playerName, $senderName, $rank) {
            $mysqli->query("UPDATE PlayerPerms SET rankname='$rank' WHERE playername='$playerName'");
            return true;
        }, function (Server $server, bool $success) use ($senderName, $playerName, $rank) {
            if(($player = Server::getInstance()->getPlayerExact($senderName)) != null) {
                if($success)
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Der Spieler ".TextFormat::AQUA.$playerName.TextFormat::GRAY." hat den Rang ".TextFormat::AQUA.$rank.TextFormat::GREEN." erhalten.");
                else
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :/");
            }
        });
    }

    /**
     * @param string $rank
     * @param string $senderName
     * @param int $joinPower
     */
    public static function setJoinPower(string $rank, string $senderName, int $joinPower)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($joinPower, $senderName, $rank){
            $mysqli->query("UPDATE `Ranks` SET joinpower='$joinPower' WHERE rankname='$rank'");
            return true;
        }, function (Server $server, bool $success) use ($senderName, $rank, $joinPower){
            if(($player = Server::getInstance()->getPlayerExact($senderName)) != null) {
                if($success)
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Der Rang ".TextFormat::AQUA.$rank.TextFormat::GRAY." hat die JoinPower ".TextFormat::AQUA.$joinPower.TextFormat::GRAY." zugewiesen bekommen.");
                else
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :/");
            }
        });
    }

    /**
     * @param string $playerName
     * @param string $senderName
     * @param string $permission
     */
    public static function removePlayerPermission(string $playerName, string $senderName, string $permission)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($playerName, $senderName, $permission){
            $result = $mysqli->query("SELECT permissions From PlayerPerms WHERE playername='$playerName'");
            $newPermissions = "";
            if($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    foreach (explode(":", $data['permissions']) as $permission) {
                        if($permission != $this->permission) {
                            if($newPermissions == "") {
                                $newPermissions = $this->permission;
                            }else {
                                $newPermissions = $newPermissions . ":".$this->permission;
                            }
                        }
                    }
                }
                $mysqli->query("UPDATE PlayerPerms SET permissions='$newPermissions' WHERE playername='$playerName'");
                return true;
            }
            return false;
        }, function (Server $server, bool $success) use ($permission, $senderName, $playerName){
            if(($player = Server::getInstance()->getPlayerExact($senderName)) != null) {
                if($success)
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Die Permission ".TextFormat::AQUA.$permission.TextFormat::GRAY." wurde dem Spieler ".TextFormat::AQUA.$playerName.TextFormat::RED." entfernt.");
                else
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :/");
            }
        });
    }

    /**
     * @param string $playerName
     * @param string $senderName
     * @param string $permission
     */
    public static function addPermToPlayer(string $playerName, string $senderName, string $permission)
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($senderName, $playerName, $permission){
            $result = $mysqli->query("SELECT permissions From PlayerPerms WHERE playername='$playerName'");
            $newPermissions = "";
            if($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    if($data['permissions'] == "") {
                        $newPermissions .= $this->permission;
                    }else {
                        $newPermissions .= $data['permissions'].":".$this->permission;
                    }
                }
                $mysqli->query("UPDATE PlayerPerms SET permissions='$newPermissions' WHERE playername='$playerName'");
                return true;
            }
            return false;
        }, function (Server $server, bool $success) use ($senderName, $playerName, $permission){
            if(($player = Server::getInstance()->getPlayerExact($senderName)) != null) {
                if($success)
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Die Permission ".TextFormat::AQUA.$permission.TextFormat::GRAY." wurde dem Spieler ".TextFormat::AQUA.$playerName.TextFormat::GREEN." hinzugefügt.");
                else
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Etwas ist schief gelaufen :/");
            }
        });
    }


    /**
     * @param array $perms
     * @return array
     */
    public static function getPermFormat(array $perms): array
    {
        $endperm = [];
        foreach ($perms as $perm) {
            if ($perm == "*") {
                foreach(PermissionManager::getInstance()->getPermissions() as $permission) {
                    $endperm[$permission->getName()] = true;
                }
            } else {
                $endperm[$perm] = true;
            }
        }
        return $endperm;
    }

    /**
     * @param RyzerPlayer $player
     * @param string $message
     * @return string
     */
    public static function returnChatPrefix(RyzerPlayer $player, string $message): string
    {
        if($player->isToggleRank() && $player->getNick() == null) {
            $prefix = self::getChatPrefix("Player");
        }else {
            $prefix = self::getChatPrefix(($player->getNick() == null) ? $player->getRank() : "Player");
        }
        $prefix = str_replace("&", TextFormat::ESCAPE, $prefix);
        $prefix = str_replace("{player_name}", ($player->getNick() == null) ? $player->getPlayer()->getName() : $player->getNick(), $prefix);
        if($player->getClan() === null)
        return str_replace("{MSG}", $message, $prefix);
        else
            return str_replace("&", TextFormat::ESCAPE, $player->getClan()->getClanTag())." ".str_replace("{MSG}", $message, $prefix);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isNicked(string $name): bool
    {
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($name)) != null) {
            return $obj->getNick() != null;
        }

        return false;
    }

    /**
     * @param string $playerName
     * @return string|null
     */
    public static function getNickName(string $playerName): ?String
    {
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
            return $obj->getNick();
        }
        return null;
    }
}