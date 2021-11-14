<?php

namespace ryzerbe\core\provider;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMessagePacket;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\util\async\AsyncExecutor;
use function array_search;
use function implode;
use function in_array;
use function str_replace;

class StaffProvider implements RyZerProvider {
    /** @var string[]  */
    private static array $loggedIn = [];

    /**
     * @return string[]
     */
    public static function getLoggedIn(): array{
        return self::$loggedIn;
    }

    public static function login(RyZerPlayer|Player|string $player): void{
        if(self::loggedIn($player)) return;

        if($player instanceof RyZerPlayer) $player = $player->getPlayer()->getName();
        if($player instanceof Player) $player = $player->getName();

        self::$loggedIn[] = $player;
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($player): void{
            $mysqli->query("INSERT INTO `staffs`(`player`) VALUES ('$player')");
        });
        StaffProvider::sendMessageToStaffs(TextFormat::GREEN.$player.TextFormat::GRAY." hat sich §aeingeloggt§7.", true);
    }

    public static function loggedIn(RyZerPlayer|Player|string $player): bool{
        if($player instanceof RyZerPlayer) $player = $player->getPlayer()->getName();
        if($player instanceof Player) $player = $player->getName();


        return in_array($player, self::$loggedIn);
    }

    public static function logout(RyZerPlayer|Player|string $player): void{
        if(!self::loggedIn($player)) return;
        if($player instanceof RyZerPlayer) $player = $player->getPlayer()->getName();
        if($player instanceof Player) $player = $player->getName();

        StaffProvider::sendMessageToStaffs(TextFormat::GREEN.$player.TextFormat::GRAY." hat sich §causgeloggt§7.", true);
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($player): void{
            $mysqli->query("DELETE FROM `staffs` WHERE player='$player'");
        });

        unset(self::$loggedIn[array_search($player, self::$loggedIn)]);
    }

    public static function refresh(): void{
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli): array{
            $res = $mysqli->query("SELECT * FROM staffs");
            $staffs = [];
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()){
                    $staffs[] = $data["player"];
                }
            }

            return $staffs;
        }, function(Server $server, array $staffs): void{
            StaffProvider::$loggedIn = $staffs;
        });
    }

    /**
     * @param string $message
     * @param bool $proxy
     */
    public static function sendMessageToStaffs(string $message, bool $proxy){
        if($proxy) {
            $pk = new PlayerMessagePacket();
            $pk->addData("players", implode(":", self::getLoggedIn()));
            $pk->addData("message", str_replace("§", "&", $message));
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            return;
        }

        foreach(self::getLoggedIn() as $staff) {
            $player = Server::getInstance()->getPlayerExact($staff);
            if($player === null) continue;

            $player->sendMessage($message);
        }
    }
}