<?php

namespace ryzerbe\core\provider;

use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\util\async\AsyncExecutor;
use function array_search;
use function in_array;

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
}