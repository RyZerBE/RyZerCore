<?php

namespace ryzerbe\core\provider;

use DateTime;
use Exception;
use mysqli;

class PartyProvider implements RyZerProvider {

    const PARTY_OPEN = 0;
    const PARTY_CLOSED = 1;

    const PARTY_ROLE_MEMBER = 0;
    const PARTY_ROLE_MODERATOR = 1;
    const PARTY_ROLE_LEADER = 2;

    const SUCCESS = 0;
    const NO_PERMISSION = 1;
    const NO_PARTY = 2;
    const NO_PARTY_PLAYER = 2;
    const NO_REQUEST = 7;
    const ALREADY_IN_PARTY = 3;
    const ALREADY_REQUEST = 4;
    const ALREADY_BANNED = 8;
    const ALREADY_UNBANNED = 9;
    const PARTY_CLOSE = 6;
    const PARTY_DOESNT_EXIST = 5;
    const PLAYER_DOESNT_EXIST = 10;

    /**
     * @param mysqli $mysqli
     * @param string $player
     * @return bool
     */
    public static function validPlayer(mysqli $mysqli, string $player): bool{
       $res = $mysqli->query("SELECT * FROM coins WHERE player='$player'");
       return $res->num_rows > 0;
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getRoleNameById(int $id): string{
        return match ($id) {
            self::PARTY_ROLE_MEMBER => "Member",
            self::PARTY_ROLE_MODERATOR => "Moderator",
            self::PARTY_ROLE_LEADER => "Leader",
            default => "???"
        };
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @param int $open
     */
    public static function createParty(mysqli $mysqli, string $owner, int $open = self::PARTY_CLOSED): void{
        $mysqli->query("INSERT INTO `party`(`owner`, `open`) VALUES ('$owner', '$open')");
        self::joinParty($mysqli, $owner, $owner, self::PARTY_ROLE_LEADER);
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     */
    public static function deleteParty(mysqli $mysqli, string $owner): void{
        $mysqli->query("DELETE FROM `party` WHERE owner='$owner'");
        foreach(self::getPartyMembers($mysqli, $owner) as $member) {
            self::leaveParty($mysqli, $member, $owner);
        }
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @param bool $open
     */
    public static function openParty(mysqli $mysqli, string $owner, bool $open = true){
        $open = ($open === true) ? self::PARTY_OPEN : self::PARTY_CLOSED;
        $mysqli->query("UPDATE `party` SET open='$open' WHERE owner='$owner'");
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @return bool
     */
    public static function isPartyOpen(mysqli $mysqli, string $owner): bool{
        $res = $mysqli->query("SELECT * FROM party WHERE owner='$owner'");
        if($data = $res->fetch_assoc()) {
            return $data["open"] == self::PARTY_OPEN;
        }

        return false;
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @param string $player
     */
    public static function addRequest(mysqli $mysqli, string $owner, string $player){
        $mysqli->query("INSERT INTO `partyrequest`(`player`, `party`) VALUES ('$player', '$owner')");
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @param string $player
     */
    public static function removeRequest(mysqli $mysqli, string $owner, string $player){
        $mysqli->query("DELETE FROM `partyrequest` WHERE player='$player' AND party='$owner'");
    }

    /**
     * @param mysqli $mysqli
     * @param string $player
     * @return array
     * @throws Exception
     */
    public static function getRequests(mysqli $mysqli, string $player): array{
        $res = $mysqli->query("SELECT * FROM partyrequest WHERE player='$player'");
        if($res->num_rows <= 0) return [];

        $members = [];
        while($data = $res->fetch_assoc()) {
            if((new DateTime($data["time"]))->diff(new DateTime())->m >= 1){
                self::removeRequest($mysqli, $data["party"], $player);
                continue;
            }
            $members[] = $data["party"];
        }

        return $members;
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @param string $player
     * @return bool
     */
    public static function hasRequest(mysqli $mysqli, string $owner, string $player): bool{
        $res = $mysqli->query("SELECT * FROM partyrequest WHERE player='$player' AND party='$owner'");
        return $res->num_rows > 0;
    }

    /**
     * @param mysqli $mysqli
     * @param string $player
     * @param string $owner
     * @param int $role
     */
    public static function joinParty(mysqli $mysqli, string $player, string $owner, int $role = self::PARTY_ROLE_MEMBER){
        $mysqli->query("INSERT INTO `partymember`(`player`, `party`, `role`) VALUES ('$player', '$owner', '$role')");
    }

    /**
     * @param mysqli $mysqli
     * @param string $player
     * @param string $owner
     */
    public static function leaveParty(mysqli $mysqli, string $player, string $owner){
        $mysqli->query("DELETE FROM `partymember` WHERE player='$player' AND party='$owner'");
    }

    /**
     * @param mysqli $mysqli
     * @param string $party
     * @param string $sender
     * @param string $player
     * @return bool
     */
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

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @param string $player
     * @param string $sender
     * @return bool
     */
    public static function unbanFromParty(mysqli $mysqli, string $owner, string $player, string $sender): bool{
        $playerRole = self::getPlayerRole($mysqli, $sender, false);
        if($playerRole === null) return false;
        if($playerRole < self::PARTY_ROLE_MODERATOR) return false;

        $mysqli->query("DELETE FROM `partyban` WHERE party='$owner' AND player='$player'");
        return true;
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @param string $player
     * @return bool
     */
    public static function isBannedFromParty(mysqli $mysqli, string $owner, string $player): bool{
        $res = $mysqli->query("SELECT * FROM partyban WHERE party='$owner' AND player='$player'");
        return $res->num_rows > 0;
    }

    /**
     * @param mysqli $mysqli
     * @param string $player
     * @param string $owner
     * @param int $role
     */
    public static function updateRole(mysqli $mysqli, string $player, string $owner, int $role){
        $mysqli->query("UPDATE `partymember` SET role='$role' WHERE player='$player' AND party='$owner'");
    }

    /**
     * @param mysqli $mysqli
     * @param string $owner
     * @return array
     */
    public static function getPartyMembers(mysqli $mysqli, string $owner): array{
        $res = $mysqli->query("SELECT * FROM partymember WHERE party='$owner'");
        if($res->num_rows <= 0) return [];

        $members = [];
        while($data = $res->fetch_assoc()) {
            $members[] = $data["player"];
        }

        return $members;
    }

    /**
     * @param mysqli $mysqli
     * @param string $player
     * @return string|null
     */
    public static function getPartyByPlayer(mysqli $mysqli, string $player): ?string{
        $res = $mysqli->query("SELECT * FROM partymember WHERE player='$player'");
        if($res->num_rows <= 0) return null;

        return $res->fetch_assoc()["party"] ?? null;
    }

    /**
     * @param mysqli $mysqli
     * @param string $player
     * @param bool $asName
     * @return int|null|string
     */
    public static function getPlayerRole(mysqli $mysqli, string $player, bool $asName): int|string|null{
        $res = $mysqli->query("SELECT * FROM partymember WHERE player='$player'");
        if($res->num_rows <= 0) return null;

        $role = $res->fetch_assoc()["role"];
        return ($asName === true) ? self::getRoleNameById($role) : (int)$role;
    }
}