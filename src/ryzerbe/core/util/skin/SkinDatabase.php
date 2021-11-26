<?php

namespace ryzerbe\core\util\skin;

use Closure;
use mysqli;
use pocketmine\entity\Skin;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\cache\CacheTrait;

class SkinDatabase {
    use SingletonTrait;
    use CacheTrait;

    /**
     * @param string $skin
     * @param Closure $closure
     * @param string|null $version
     * @param Player|null $setSkinToPlayer
     */
    public function loadSkin(string $skin, Closure $closure, string|null $version = null, ?Player $setSkinToPlayer = null): void{
        $__skin = $this->getSkin($skin);
        if($__skin !== null){
            $closure(true);
            if($setSkinToPlayer instanceof Player) {
                $setSkinToPlayer->setSkin($__skin);
                $setSkinToPlayer->sendSkin();
            }
            return;
        }
        $microtime = microtime(true);
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($skin, $version): ?array{
            $query = $mysqli->query("SELECT * FROM skins WHERE skin_name='$skin'" . ($version !== null ? " AND version='$version'" : "") . " ORDER BY id DESC");
            if($query->num_rows <= 0) return null;
            $assoc = $query->fetch_assoc();
            return [
                "skinData" => zlib_decode(base64_decode($assoc["skinData"])),
                "geometryData" => zlib_decode(base64_decode($assoc["geometryData"])),
                "geometryName" => $assoc["geometryName"]
            ];
        }, function(Server $server, ?array $result) use ($closure, $skin, $microtime, $setSkinToPlayer): void{
            if(!empty($result)) $server->getLogger()->info(RyZerBE::PREFIX . " Took " . round(microtime(true) - $microtime, 5) . " seconds to load " . $skin . " from database.");
            $closure(!empty($result));
            $__skin = new Skin($skin, $result["skinData"], "", $result["geometryName"], $result["geometryData"]);
            SkinDatabase::getInstance()->getCache()->set($skin, $__skin);
            if($setSkinToPlayer instanceof Player) {
                $setSkinToPlayer->setSkin($__skin);
                $setSkinToPlayer->sendSkin();
            }
        });
    }

    /**
     * @param string $name
     * @return Skin|null
     */
    public function getSkin(string $name): ?Skin{
        return $this->getCache()->get($name);
    }

    /**
     * @param Skin $skin
     * @param string $name
     * @param string $version
     */
    public function saveSkin(Skin $skin, string $name, string $version): void{
        $skinData = $skin->getSkinData();
        $geometryData = $skin->getGeometryData();
        $geoName = $skin->getGeometryName();
        $microtime = microtime(true);
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($geometryData, $skinData, $version, $name, $geoName): void{
            $skinData = base64_encode(zlib_encode($skinData, ZLIB_ENCODING_DEFLATE));
            $geometryData = base64_encode(zlib_encode($geometryData, ZLIB_ENCODING_DEFLATE));
            $mysqli->query("INSERT INTO skins(skin_name, version, skinData, geometryData, geometryName) VALUES ('$name', '$version', '$skinData', '$geometryData', '$geoName')");
        }, function(Server $server, $result) use ($microtime, $name, $version): void{
            $message = RyZerBE::PREFIX. " Took " . round(microtime(true) - $microtime, 5) . " seconds to store " . $name . " " . $version . " in database.";
            $server->broadcastMessage($message, array_filter(Server::getInstance()->getOnlinePlayers(), function(PMMPPlayer $player): bool{
                return $player->hasPermission("ryzer.admin");
            }));
            $server->getLogger()->info($message);
        });
    }
}