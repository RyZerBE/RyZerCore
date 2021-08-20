<?php


namespace baubolp\core\provider;


use BauboLP\Cloud\Bungee\BungeeAPI;
use mysqli;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class StaffProvider
{

    private static array $loggedStaffs = [];

    /**
     * @param array $loggedStaffs
     */
    public static function setLoggedStaffs(array $loggedStaffs): void
    {
        self::$loggedStaffs = $loggedStaffs;
    }

    /**
     * @param string $playerName
     * @return bool
     */
    public static function isLogin(string $playerName): bool
    {
        return in_array($playerName, self::$loggedStaffs);
    }

    public static function login(string $playerName)
    {
        Server::getInstance()->getAsyncPool()->submitTask(new class(MySQLProvider::getMySQLData(), $playerName) extends AsyncTask{

            private array $mysqlData;
            private string $name;

            public function __construct(array $mysqlData, string $name)
            {
                $this->mysqlData = $mysqlData;
                $this->name = $name;
            }

            public function onRun()
            {
                $mysqli = new mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $name = $this->name;
                $mysqli->query("INSERT INTO `Staffs`(`playername`) VALUES ('$name')");
                $mysqli->close();
            }
        });

        self::$loggedStaffs[] = $playerName;
    }

    public static function logout(string $playerName)
    {
        Server::getInstance()->getAsyncPool()->submitTask(new class(MySQLProvider::getMySQLData(), $playerName) extends AsyncTask{

            private array $mysqlData;
            private string $name;

            public function __construct(array $mysqlData, string $name)
            {
                $this->mysqlData = $mysqlData;
                $this->name = $name;
            }

            public function onRun()
            {
                $mysqli = new mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $name = $this->name;
                $mysqli->query("DELETE FROM `Staffs` WHERE playername='$name'");
                $mysqli->close();
            }
        });

        unset(self::$loggedStaffs[array_search($playerName, self::$loggedStaffs)]);
    }

    public static function getLoggedStaff(): array
    {
        return self::$loggedStaffs;
    }

    /**
     * @param string $message
     */
    public static function sendMessageToStaffs(string $message)
    {
        foreach (self::getLoggedStaff() as $staff)
            BungeeAPI::sendMessage($message, $staff);
    }
}