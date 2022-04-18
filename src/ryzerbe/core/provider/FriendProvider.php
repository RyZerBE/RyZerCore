<?php
declare(strict_types=1);
namespace ryzerbe\core\provider;

use mysqli;

class FriendProvider implements RyZerProvider{

	const PLAYER_SLOT_COUNT = 20;
	const PRIME_SLOT_COUNT = 50;
	const MEDIA_SLOT_COUNT = 100;

	public static function validPlayer(mysqli $mysqli, string $player): bool{
		$res = $mysqli->query("SELECT * FROM coins WHERE player='$player'");
		return $res->num_rows > 0;
	}

	public static function addFriendRequest(mysqli $mysqli, string $sender, string $addFriend): void{
		$res = $mysqli->query("SELECT * FROM friend_request WHERE sender='$sender' AND player='$addFriend'");
		$re2 = $mysqli->query("SELECT * FROM friend_request WHERE sender='$addFriend' AND player='$sender'");
		if($res->num_rows > 0) return;
		if($re2->num_rows > 0) {
			self::addFriend($mysqli, $sender, $addFriend);
			return;
		}

		$mysqli->query("INSERT INTO `friend_request`(`sender`, `player`) VALUES ('$sender', '$addFriend')");
	}

	public static function addFriend(mysqli $mysqli, string $sender, string $player): void{
		self::removeFriendRequest($mysqli, $sender, $player);

		$mysqli->query("INSERT INTO `friends`(`friend_1`, `friend_2`) VALUES ('$sender', '$player')");
	}

	public static function allowFriendRequest(mysqli $mysqli, string $player): bool{
		$res = $mysqli->query("SELECT friend_request FROM player_settings WHERE player='$player'");
		if($res->num_rows <= 0) return true;

		if($data = $res->fetch_assoc()) {
			return $data["friend_request"];
		}

		return true;
	}

	public static function removeFriend(mysqli $mysqli, string $sender, string $player): void{
		self::removeFriendRequest($mysqli, $sender, $player);

		$mysqli->query("DELETE FROM `friends` WHERE friend_1='$sender' OR friend_2='$sender'");
	}

	public static function isFriend(mysqli $mysqli, string $sender, string $player): bool{
		$res = $mysqli->query("SELECT * FROM friends WHERE (friend_1='$sender' AND friend_2='$player') OR (friend_1='$player' AND friend_2='$sender')");
		return $res->num_rows > 0;
	}

	public static function removeFriendRequest(mysqli $mysqli, string $sender, string $addFriend): void{
		$mysqli->query("DELETE FROM `friend_request` WHERE player='$addFriend' OR player='$sender'");
	}

	public static function getFriends(mysqli $mysqli, string $player): ?array{
		$res = $mysqli->query("SELECT * FROM friends WHERE friend_1='$player' OR friend_2='$player'");
		if($res->num_rows <= 0) return null;

		$friends = [];
		while($data = $res->fetch_assoc()) {
			$friends[] = ($data["friend_1"] === $player) ? $data["friend_2"] : $data["friend_1"];
		}

		return $friends;
	}
}
