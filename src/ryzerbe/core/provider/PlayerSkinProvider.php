<?php

namespace ryzerbe\core\provider;

use mysqli;
use pocketmine\entity\Skin;
use pocketmine\Player;

class PlayerSkinProvider implements RyZerProvider {

    /**
     * @param Player|string $player
     * @param string $skinDataRaw
     * @param string $geometryName
     * @param mysqli $mysqli
     */
    public static function storeSkin(Player|string $player, string $skinDataRaw, string $geometryName, mysqli $mysqli): void{
        if($player instanceof Player) $player = $player->getName();

        $skinType = (int)($geometryName === 'geometry.humanoid.custom' || $geometryName === 'geometry.humanoid.steve');
        $skinData = base64_encode(zlib_encode($skinDataRaw, ZLIB_ENCODING_DEFLATE));
        $skinHash = sha1($skinData);
        if(($query = $mysqli->query("SELECT id FROM player_skins WHERE player = '$player' AND skin_hash = '$skinHash'"))->num_rows > 0){
            $skinId = $query->fetch_array()[0];
            $mysqli->query("UPDATE player_skins SET in_use = IF(id = $skinId, 1, 0) WHERE player = '$player'");
            return;
        }
        $mysqli->query("INSERT INTO player_skins(player, skin_data, skin_hash, skin_type) VALUES ('$player','$skinData','$skinHash','$skinType')");
        $mysqli->query("UPDATE player_skins SET in_use = IF(id = LAST_INSERT_ID(), 1, 0) WHERE player = '$player'");
    }

    /**
     * @param Player|string $player
     * @param mysqli $mysqli
     * @return Skin|null
     */
    public static function getSkin(Player|string $player, mysqli $mysqli): ?Skin{
        if($player instanceof Player) $player = $player->getName();

        $skinQuery = $mysqli->query("SELECT id, skin_data, skin_type FROM player_skins WHERE player = '$player' AND in_use = 1");
        if($skinQuery->num_rows === 0) return null;
        $skinResult = $skinQuery->fetch_assoc();
        return new Skin($skinResult["id"], zlib_decode(base64_decode($skinResult["skin_data"])), "", $skinResult["skin_type"] === "1" ? "geometry.humanoid.custom" : "geometry.humanoid.customSlim");
    }

    /**
     * @param Player|string $player
     * @param mysqli $mysqli
     * @return array|null
     */
    public static function getAll(Player|string $player, mysqli $mysqli): ?array{
        if($player instanceof Player) $player = $player->getName();

        $query = $mysqli->query("SELECT * FROM player_skins WHERE player = '$player'");
        if($query->num_rows <= 0) return null;
        return $query->fetch_assoc();
    }
}