<?php


namespace baubolp\core\provider;


use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMessagePacket;
use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\Ryzer;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class JoinMEProvider
{
    /** @var array  */
    public static $joinMe = [];

    const TEXTFORMAT_RGB = [
        [0, 0, 0],
        [0, 0, 170],
        [0, 170, 0],
        [0, 170, 170],
        [170, 0, 0],
        [170, 0, 170],
        [255, 170, 0],
        [170, 170, 170],
        [85, 85, 85],
        [85, 85, 255],
        [85, 255, 85],
        [85, 255, 255],
        [255, 85, 85],
        [255, 85, 255],
        [255, 255, 85],
        [255, 255, 255]
    ];

    const TEXTFORMAT_LIST = [
        TextFormat::BLACK,
        TextFormat::DARK_BLUE,
        TextFormat::DARK_GREEN,
        TextFormat::DARK_AQUA,
        TextFormat::DARK_RED,
        TextFormat::DARK_PURPLE,
        TextFormat::GOLD,
        TextFormat::GRAY,
        TextFormat::DARK_GRAY,
        TextFormat::BLUE,
        TextFormat::GREEN,
        TextFormat::AQUA,
        TextFormat::RED,
        TextFormat::LIGHT_PURPLE,
        TextFormat::YELLOW,
        TextFormat::WHITE
    ];

    private static $forbiddenGroups = [
        'Lobby',
        'CWBW',
        'Clutches'
    ];

    /**
     * @param $r
     * @param $g
     * @param $b
     * @return mixed
     */
    public static function rgbToTextFormat($r, $g, $b){
        $differenceList = [];
        foreach(self::TEXTFORMAT_RGB as $value){
            $difference = sqrt(pow($r - $value[0],2) + pow($g - $value[1],2) + pow($b - $value[2],2));
            $differenceList[] = $difference;
        }
        $smallest = min($differenceList);
        $key = array_search($smallest, $differenceList);
        return self::TEXTFORMAT_LIST[$key];
    }

    /**
     * @param Player $player
     * @return array
     */
    public static function convertSkinToArray(Player $player) {
        $skinArray = [0 => "", 1 => "", 2 => "", 3 => "", 4 => "", 5 => "", 6 => "", 7 => ""];
        $skin = substr(serialize($player->getSkin()->getSkinImage()->getData()), ($pos = (64 * 8 * 4)) - 4, $pos);
        for($y = 0; $y < 8; ++$y){
            for($x = 1; $x < 9; ++$x){
                if(!isset($skinArray[$y]))
                    $strArray[$y] = "????????"; // 3D Skin
                $key = ((64 * $y) + 8 + $x) * 4;
                $r = ord($skin{$key});
                $g = ord($skin{$key + 1});
                $b = ord($skin{$key + 2});
                $format = self::rgbToTextFormat($r, $g, $b);
                $skinArray[$y] .= $format."█"; //█
            }
        }
        return $skinArray;
    }

    /**
     * @param \pocketmine\Player $player
     */
    public static function createJoinMe(Player $player)
    {
       //$head = self::getMessageFromArray(self::convertSkinToArray($player))."\n";
       // $message = "\n\n".TextFormat::AQUA."JoinMe ".TextFormat::DARK_GRAY."| ".$player->getNameTag().TextFormat::RESET.TextFormat::GRAY." created a JoinME on ".TextFormat::BOLD.TextFormat::GOLD.CloudProvider::getServer().TextFormat::RESET.TextFormat::GRAY.".\n"
       //            .TextFormat::AQUA."JoinMe ".TextFormat::DARK_GRAY."| ".TextFormat::GRAY."Join him/her with ".TextFormat::BOLD.TextFormat::GOLD."/joinme".TextFormat::RESET.TextFormat::GRAY.".\n\n";
        $message = "\n\n"."&bJoinMe &8| &a".$player->getName()." &r&7created a JoinME on &6".CloudProvider::getServer()."&7.\n".
                          "&bJoinMe &8| &7Join him with &6/joinme&7.\n\n";
        $pk = new PlayerMessagePacket();
        $pk->addData("players", "ALL");
        $pk->addData("message", $message);
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);

        $name = $player->getName();
        $serverName = CloudProvider::getServer();
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (\mysqli $mysqli) use ($name, $serverName){
            $mysqli->query("INSERT INTO `JoinMe`(`playername`, `server`) VALUES ('$name', '$serverName')");
        }, function (Server $server, $result) use ($name, $serverName){
            if(($player = $server->getPlayerExact($name)) != null)
                $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('joinme-created', $player->getName(), ['#server' => $serverName]));
            JoinMEProvider::$joinMe[$name] = time() + 40;
        });
    }

    /**
     * @param string $playerName
     */
    public static function deleteJoinMeIfExist(string $playerName)
    {
        if(isset(self::$joinMe[$playerName])) {
            self::removeJoinMe($playerName, true);
        }
    }

    /**
     * @param string $playerName
     * @param bool $mysql
     */
    public static function removeJoinMe(string $playerName, bool $mysql = true)
    {
        if (isset(self::$joinMe[$playerName])) {
            unset(self::$joinMe[$playerName]);
            if ($mysql) {
                AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (\mysqli $mysqli) use ($playerName) {
                    $mysqli->query("DELETE FROM JoinMe WHERE playername='$playerName'");
                });
            }
        }
    }

    /**
     * @param string $playerName
     * @return bool
     */
    public static function existJoinMe(string $playerName)
    {
        return isset(self::$joinMe[$playerName]);
    }

    /**
     * @param array $skin
     * @return string
     */
    public static function getMessageFromArray(array $skin) {
        return implode("\n", $skin);
    }

    /**
     * @return bool
     */
    public static function isServerForbidden(): bool
    {
        foreach (self::$forbiddenGroups as $group) {
            if(stripos(CloudProvider::getServer(), (string)$group) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getForbiddenGroups(): array
    {
        return self::$forbiddenGroups;
    }
}