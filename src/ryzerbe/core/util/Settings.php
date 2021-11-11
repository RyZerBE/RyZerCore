<?php

namespace ryzerbe\core\util;

use mysqli;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\util\async\AsyncExecutor;
use function file_exists;
use function var_dump;

class Settings {
    use SingletonTrait;

    public static bool $reduce = false;
    public static array $mysqlLoginData = [];

    public static array $ips = [
        "5.181.151.61",
        "127.0.0.1"
    ];

    public static array $autoMessages = [
        "automessage-1",
        "automessage-2",
        "automessage-3",
        "automessage-4",
        "automessage-5",
        "automessage-6"
    ];

    public function initMySQL(): void{
        if(!file_exists("/root/RyzerCloud/data/mysql.json")) {
            $config = new Config("/root/RyzerCloud/data/mysql.json", Config::JSON);
            $config->set("login", [
                "host" => "host:3306",
                "username" => "username",
                "password" => "password"
            ]);
            $config->save();
        }

        $config = new Config("/root/RyzerCloud/data/mysql.json", Config::JSON);
        self::$mysqlLoginData = $config->getAll()["login"];


        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli): void{
            $mysqli->query("CREATE TABLE IF NOT EXISTS `playerlanguage` (`player` VARCHAR(32) NOT NULL, `language` VARCHAR(16) NOT NULL DEFAULT 'English') ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `coins` (`player` VARCHAR(32) NOT NULL, `coins` INT NOT NULL DEFAULT '0') ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `party` (`owner` VARCHAR(32) NOT NULL, `open` INT NOT NULL DEFAULT '1') ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `partymember` (`player` VARCHAR(32) NOT NULL, `party` VARCHAR(32) NOT NULL, `role` INT NOT NULL DEFAULT '0') ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `partyrequest` (`player` VARCHAR(32) NOT NULL, `party` VARCHAR(32) NOT NULL, 'time' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `partyban` (`player` VARCHAR(32) NOT NULL, `party` VARCHAR(32) NOT NULL) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `ranks` (`rankname` VARCHAR(32) KEY NOT NULL, `nametag` TEXT NOT NULL, `chatprefix` TEXT NOT NULL, `permissions` TEXT NOT NULL, `joinpower` INT NOT NULL, `color` varchar(5) NOT NULL) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `gametime` (`player` VARCHAR(32) KEY NOT NULL, `ticks` INT NOT NULL DEFAULT '0') ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `verify` ( `player` VARCHAR(32) NOT NULL , `token` VARCHAR(8) NOT NULL , `verified` TEXT NOT NULL ) ENGINE = InnoDB;");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `joinme` (`player` VARCHAR(32) KEY NOT NULL, `server` VARCHAR(8) NOT NULL, time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `vanish` (`player` VARCHAR(32) KEY NOT NULL) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `staffs` (`player` VARCHAR(32) KEY NOT NULL) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `playerranks` (`player` VARCHAR(32) NOT NULL, `rankname` VARCHAR(16) NOT NULL DEFAULT 'Player', `permissions` TEXT NOT NULL) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `punishids` (`reason` VARCHAR(32) NOT NULL, `days` INT NOT NULL, `hours` INT NOT NULL, `type` INT NOT NULL) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `punishments` (id INTEGER NOT NULL KEY AUTO_INCREMENT, `player` VARCHAR(32) NOT NULL, `created_by` VARCHAR(32) NOT NULL, `created_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `type` INT NOT NULL, `until` VARCHAR(64) NOT NULL, `reason` varchar(32) NOT NULL) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS networklevel(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL, level INTEGER NOT NULL DEFAULT '1', level_progress INTEGER NOT NULL DEFAULT '0', level_progress_today INTEGER NOT NULL DEFAULT '0', last_level_progress TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        });
    }
}