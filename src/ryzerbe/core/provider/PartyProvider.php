<?php

namespace ryzerbe\core\provider;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMessagePacket;
use DateTime;
use pocketmine\Server;
use mysqli;
use ryzerbe\core\util\async\AsyncExecutor;
use function implode;
use function var_dump;

class PartyProvider implements RyZerProvider {
    public const PARTY_OPEN = 0;
    public const PARTY_CLOSED = 1;

    public const PARTY_ROLE_MEMBER = 0;
    public const PARTY_ROLE_MODERATOR = 1;
    public const PARTY_ROLE_LEADER = 2;

    public const SUCCESS = 0;
    public const NO_PERMISSION = 1;
    public const NO_PARTY = 2;
    public const NO_PARTY_PLAYER = 2;
    public const NO_REQUEST = 7;
    public const ALREADY_IN_PARTY = 3;
    public const ALREADY_REQUEST = 4;
    public const ALREADY_BANNED = 8;
    public const ALREADY_UNBANNED = 9;
    public const PARTY_CLOSE = 6;
    public const PARTY_DOESNT_EXIST = 5;
    public const PLAYER_DOESNT_EXIST = 10;

    public static function validPlayer(mysqli $mysqli, string $player): bool{
       $res = $mysqli->query("SELECT * FROM coins WHERE player='$player'");
       return $res->num_rows > 0;
    }

    public static function getRoleNameById(int $id): string{
        return match ($id) {
            self::PARTY_ROLE_MEMBER => "Member",
            self::PARTY_ROLE_MODERATOR => "Moderator",
            self::PARTY_ROLE_LEADER => "Leader",
            default => "???"
        };
    }

    public static function createParty(mysqli $mysqli, string $owner, int $open = self::PARTY_CLOSED): void{
        $mysqli->query("INSERT INTO `party`(`owner`, `open`) VALUES ('$owner', '$open')");
        self::joinParty($mysqli, $owner, $owner, self::PARTY_ROLE_LEADER);
    }

    public static function deleteParty(mysqli $mysqli, string $owner): void{
        $mysqli->query("DELETE FROM `party` WHERE owner='$owner'");
        foreach(self::getPartyMembers($mysqli, $owner) as $member) {
            self::leaveParty($mysqli, $member, $owner);
        }
    }

    public static function openParty(mysqli $mysqli, string $owner, bool $open = true){
        $open = ($open === true) ? self::PARTY_OPEN : self::PARTY_CLOSED;
        $mysqli->query("UPDATE `party` SET open='$open' WHERE owner='$owner'");
    }

    public static function isPartyOpen(mysqli $mysqli, string $owner): bool{
        $res = $mysqli->query("SELECT * FROM party WHERE owner='$owner'");
        if($data = $res->fetch_assoc()) {
            return $data["open"] == self::PARTY_OPEN;
        }

        return false;
    }

    public static function addRequest(mysqli $mysqli, string $owner, string $player){
        $mysqli->query("INSERT INTO `partyrequest`(`player`, `party`) VALUES ('$player', '$owner')");
    }

    public static function removeRequest(mysqli $mysqli, string $owner, string $player){
        $mysqli->query("DELETE FROM `partyrequest` WHERE player='$player' AND party='$owner'");
    }

    public static function getRequests(mysqli $mysqli, string $player): array{
        $res = $mysqli->query("SELECT * FROM partyrequest WHERE player='$player'");
        if($res->num_rows <= 0) return [];

        $members = [];
        while($data = $res->fetch_assoc()) {
            $diff = (new DateTime())->diff(new DateTime($data["time"]));
            var_dump($diff->i);
            if($diff->i >= 1){
                self::removeRequest($mysqli, $data["party"], $player);
                continue;
            }
            $members[] = $data["party"];
        }

        return $members;
    }

    public static function hasRequest(mysqli $mysqli, string $owner, string $player): bool{
        $res = $mysqli->query("SELECT * FROM partyrequest WHERE player='$player' AND party='$owner'");
        return $res->num_rows > 0;
    }

    public static function joinParty(mysqli $mysqli, string $player, string $owner, int $role = self::PARTY_ROLE_MEMBER){
        $mysqli->query("INSERT INTO `partymember`(`player`, `party`, `role`) VALUES ('$player', '$owner', '$role')");
    }

    public static function leaveParty(mysqli $mysqli, string $player, string $owner){
        $mysqli->query("DELETE FROM `partymember` WHERE player='$player' AND party='$owner'");
    }

    public static function banPlayerFromParty(mysqli $mysqli, string $party, string $sender, string $player): bool{
        $playerRole = self::getPlayerRole($mysqli, $player, false);
        if($playerRole === null){
            $mysqli->query("INSERT INTO `partyban`(`player`, `party`) VALUES ('$player', '$party')");
            return true;
        }
        if($playerRole === self::PARTY_ROLE_LEADER) return false;
        if($playerRole >= self::getPlayerRole($mysqli, $sender, false)) return false;
        if($playerRole < self::PARTY_ROLE_MODERATOR) return false;

        return true;
    }

    public static function unbanFromParty(mysqli $mysqli, string $owner, string $player, string $sender): bool{
        $playerRole = self::getPlayerRole($mysqli, $sender, false);
        if($playerRole === null) return false;
        if($playerRole < self::PARTY_ROLE_MODERATOR) return false;

        $mysqli->query("DELETE FROM `partyban` WHERE party='$owner' AND player='$player'");
        return true;
    }

    public static function isBannedFromParty(mysqli $mysqli, string $owner, string $player): bool{
        $res = $mysqli->query("SELECT * FROM partyban WHERE party='$owner' AND player='$player'");
        return $res->num_rows > 0;
    }

    public static function updateRole(mysqli $mysqli, string $player, string $owner, int $role){
        $mysqli->query("UPDATE `partymember` SET role='$role' WHERE player='$player' AND party='$owner'");
    }

    public static function getPartyMembers(mysqli $mysqli, string $owner): array{
        $res = $mysqli->query("SELECT * FROM partymember WHERE party='$owner'");
        if($res->num_rows <= 0) return [];

        $members = [];
        while($data = $res->fetch_assoc()) {
            $members[] = $data["player"];
        }

        return $members;
    }

    public static function getPartyByPlayer(mysqli $mysqli, string $player): ?string{
        $res = $mysqli->query("SELECT * FROM partymember WHERE player='$player'");
        if($res->num_rows <= 0) return null;

        return $res->fetch_assoc()["party"] ?? null;
    }

    public static function getPlayerRole(mysqli $mysqli, string $player, bool $asName): int|string|null{
        $res = $mysqli->query("SELECT * FROM partymember WHERE player='$player'");
        if($res->num_rows <= 0) return null;

        $role = $res->fetch_assoc()["role"];
        return ($asName === true) ? self::getRoleNameById($role) : (int)$role;
    }

    /**
     * @param string $partyOwner
     * @param string $message
     */
    public static function sendPartyMessage(string $partyOwner, string $message): void{
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($partyOwner, $message): array{
            return PartyProvider::getPartyMembers($mysqli, $partyOwner);
        }, function(Server $server, array $members) use ($message): void{
            $pk = new PlayerMessagePacket();
            $pk->addData("players", implode(":", $members));
            $pk->addData("message", $message);
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
        });
    }
}