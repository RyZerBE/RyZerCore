<?php

namespace ryzerbe\core\util;

use mysqli;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\util\async\AsyncExecutor;
use function file_exists;

class Settings {
    use SingletonTrait;

    /** @var bool  */
    public static bool $reduce = false;
    /** @var array  */
    public static array $mysqlLoginData = [];

    public function initMySQL(): void{
        $config = new Config("/root/RyzerCloud/data/mysql.json", Config::JSON);
        if(!file_exists("/root/RyzerCloud/data/mysql.json")) {
            $config->set("login", [
                "host" => "host:3306",
                "username" => "username",
                "password" => "password"
            ]);
            $config->save();
        }

        self::$mysqlLoginData = $config->getAll(true);

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli): void{
            $mysqli->query("CREATE TABLE IF NOT EXISTS `playerlanguage` (`player` VARCHAR(32) NOT NULL, `language` VARCHAR(16) NOT NULL DEFAULT 'English') ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `coins` (`player` VARCHAR(32) NOT NULL, `coins` INT NOT NULL DEFAULT '0') ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `ranks` (`rankname` VARCHAR(32) NOT NULL, `nametag` TEXT NOT NULL, `chatprefix` TEXT NOT NULL, `permissions` TEXT NOT NULL, `joinpower` INT NOT NULL, `color` varchar(5) NOT NULL) ENGINE = InnoDB");
            $mysqli->query("CREATE TABLE IF NOT EXISTS `playerranks` (`player` VARCHAR(32) NOT NULL, `rankname` VARCHAR(16) NOT NULL DEFAULT 'Player', `permissions` TEXT NOT NULL DEFAULT '') ENGINE = InnoDB");
        });
    }
}