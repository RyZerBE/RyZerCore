<?php

namespace ryzerbe\core\provider;

use mysqli;
use pocketmine\entity\Skin;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use ryzerbe\core\event\player\nick\PlayerNickEvent;
use ryzerbe\core\event\player\nick\PlayerUnnickEvent;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\data\NickInfo;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\skin\SkinDatabase;
use ryzerbe\core\util\SkinUtils;
use function array_rand;
use function base64_decode;
use function basename;
use function count;
use function file_exists;
use function glob;
use function popen;
use function rand;
use function uniqid;
use function zlib_decode;

class NickProvider implements RyZerProvider {

    /** @var string[] */
    public static array $nickNames = [];
    /** @var Skin[] */
    public static array $nickSkins = [];

    public static function fetch(): void{
        $config = new Config("/root/RyzerCloud/data/nick/nicks.yml", Config::YAML);
        if(!file_exists("/root/RyzerCloud/data/nick/nicks.yml")){
            $config->set("Nicks", ["snipershots", "Hygo67", "NotJuzzy"]);
            $config->save();
        }

        foreach($config->get("Nicks") as $nickName) self::$nickNames[] = $nickName;

        foreach(glob("/root/RyzerCloud/data/nick/*") as $path){
            if(basename($path, ".yml") === "nicks") continue;


            $skin = SkinUtils::fromImage($path);
            $skin = new Skin(
                uniqid(),
                $skin,
                "",
                (new Config("/root/RyzerCloud/data/NPC/default_geometry.json"))->get("name"),
                (new Config("/root/RyzerCloud/data/NPC/default_geometry.json"))->get("geo")
            );
            self::$nickSkins[basename($path, ".png")] = $skin;
        }

        RyZerBE::getPlugin()->getLogger()->info("loaded ".count(self::$nickNames)." and ".count(self::$nickSkins)." nick skins");
    }

    public static function convertSkinsToSkinDB(): void{
        foreach(self::$nickSkins as $name => $skin) {
            SkinDatabase::getInstance()->saveSkin($skin, $name, "nick");
            popen("rm -r /root/RyzerCloud/data/nick/$name.png", "r");
        }
    }

    /**
     * @param string $nickName
     * @param bool $ryZerPlayer
     * @return Player|RyZerPlayer|null
     */
    public static function getPlayerByNick(string $nickName, bool $ryZerPlayer): null|Player|RyZerPlayer{
        foreach(Server::getInstance()->getOnlinePlayers() as $onlinePlayer){
            if(!$onlinePlayer instanceof PMMPPlayer) continue;
            $rbePlayer = $onlinePlayer->getRyZerPlayer();
            if($rbePlayer === null) continue;

            if($rbePlayer->getName(true) === $nickName){
                if($ryZerPlayer) return $rbePlayer;else return $onlinePlayer;
            }
        }

        return null;
    }

    /**
     * @param mysqli $mysqli
     * @param bool $returnSkin
     * @return array
     */
    public static function getActiveNicks(mysqli $mysqli, bool $returnData = false): array{
        $res = $mysqli->query("SELECT * FROM nicks");
        $nicks = [];
        if($res->num_rows > 0) {
            while($data = $res->fetch_assoc()) {
                if($returnData) $nicks[$data["player"]] = ["nickName" => $data["nick"], "skin" => $data["nick_skin"], "level" => $data["nick_level"]];
                else $nicks[$data["player"]] = $data["nick"];
            }
        }

        return $nicks;
    }

    /**
     * @param PMMPPlayer $player
     */
    public static function nick(PMMPPlayer $player){
        $rbePlayer = $player->getRyZerPlayer();
        if($rbePlayer === null) return;
        if($rbePlayer->getNick() !== null) return;
        $name = $player->getName();
        $nickName = self::$nickNames[array_rand(self::$nickNames)];
        $nickLevel = rand(1, 5);

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name, $nickName, $nickLevel): ?array{
            $res = $mysqli->query("SELECT * FROM skins WHERE version='nick'");
            if($res->num_rows <= 0) return null;

            $skins = [];
            while($data = $res->fetch_assoc()) {
                $skins[] = $data["skin_name"];
            }
            $nickSkinName = $skins[array_rand($skins)];
            $query = $mysqli->query("SELECT * FROM skins WHERE skin_name='$nickSkinName' ORDER BY id DESC");
            if($query->num_rows <= 0) return null;
            $assoc = $query->fetch_assoc();

            $mysqli->query("INSERT INTO `nicks`(`player`, `nick`, `nick_skin`, `nick_level`) VALUES ('$name', '$nickName', '$nickSkinName', '$nickLevel')");
             return [
                "skinData" => zlib_decode(base64_decode($assoc["skinData"])),
                "geometryData" => zlib_decode(base64_decode($assoc["geometryData"])),
                "geometryName" => $assoc["geometryName"],
                "nickSkinName" => $nickSkinName
            ];
        }, function(Server $server, ?array $result) use ($rbePlayer, $nickName, $nickLevel): void{
            $player = $rbePlayer->getPlayer();
            if(!$player->isConnected()) return;
            if($result === null) return;
            $nickSkin = new Skin(uniqid(), $result["skinData"], "", $result["geometryName"], $result["geometryData"]);

            $rbePlayer->setNick(new NickInfo($nickName, $result["nickSkinName"], $nickLevel));
            $player->setSkin($nickSkin);
            $rbePlayer->updateStatus(null);
            $player->sendSkin();
            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('nick-set', $player));
            $ev = new PlayerNickEvent($rbePlayer, $nickName, $nickSkin);
            $ev->call();
        });
    }

    /**
     * @param PMMPPlayer $player
     */
    public static function unnick(PMMPPlayer $player): void{
        $rbePlayer = $player->getRyZerPlayer();
        if($rbePlayer === null) return;
        if($rbePlayer->getNick() === null) return;
        $name = $player->getName();
        $oldNickName = $rbePlayer->getNick();

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name): void{
            $mysqli->query("DELETE FROM nicks WHERE player='$name'");
        }, function(Server $server, $result) use ($rbePlayer, $oldNickName): void{
            $player = $rbePlayer->getPlayer();
            if(!$player->isConnected()) return;

            $rbePlayer->setNick(null);
            $rbePlayer->updateStatus(null);
            $player->setSkin($rbePlayer->getJoinSkin());
            $player->sendSkin();
            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('nick-removed', $player));
            $ev = new PlayerUnnickEvent($rbePlayer, $oldNickName);
            $ev->call();
        });
    }
}