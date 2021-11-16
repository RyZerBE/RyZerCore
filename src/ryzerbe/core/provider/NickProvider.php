<?php

namespace ryzerbe\core\provider;

use mysqli;
use pocketmine\entity\Skin;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\SkinUtils;
use function array_rand;
use function basename;
use function count;
use function file_exists;
use function glob;
use function uniqid;

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

    /**
     * @param string $name
     * @return Skin|null
     */
    public static function getNickSkinByName(string $name): ?Skin{
        return self::$nickSkins[$name] ?? null;
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
    public static function getActiveNicks(mysqli $mysqli, bool $returnSkin = false): array{
        $res = $mysqli->query("SELECT * FROM nicks");
        $nicks = [];
        if($res->num_rows > 0) {
            while($data = $res->fetch_assoc()) {
                if($returnSkin) $nicks[$data["player"]] = ["nickName" => $data["nick"], "skin" => $data["nick_skin"]];
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
        $nickSkinName = array_rand(self::$nickSkins);
        $nickSkin = self::$nickSkins[$nickSkinName];

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name, $nickSkinName, $nickName): void{
            $mysqli->query("INSERT INTO `nicks`(`player`, `nick`, `nick_skin`) VALUES ('$name', '$nickName', '$nickSkinName')");
        }, function(Server $server, $result) use ($rbePlayer, $nickName, $nickSkin): void{
            $player = $rbePlayer->getPlayer();
            if(!$player->isConnected()) return;

            $rbePlayer->setNick($nickName);
            $player->setSkin($nickSkin);
            $rbePlayer->updateStatus(null);
            $player->sendSkin();
            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('nick-set', $player));
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

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name): void{
            $mysqli->query("DELETE FROM nicks WHERE player='$name'");
        }, function(Server $server, $result) use ($rbePlayer): void{
            $player = $rbePlayer->getPlayer();
            if(!$player->isConnected()) return;

            $rbePlayer->setNick(null);
            $rbePlayer->updateStatus(null);
            $player->setSkin($rbePlayer->getJoinSkin());
            $player->sendSkin();
            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('nick-removed', $player));
        });
    }
}