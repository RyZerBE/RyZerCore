<?php


namespace baubolp\core\provider;


use baubolp\core\util\MySQL;
use pocketmine\Server;
use pocketmine\utils\Config;
use function str_replace;

class MySQLProvider
{
    const PATH = "/root/RyzerCloud/data/";
    /** @var MySQL[] */
    private static array $mysql_connections = [];

    
    public static function createConfig(): void
    {
        if(!file_exists(self::PATH."mysql.yml")) {
            $c = new Config(self::PATH."mysql.yml", Config::YAML);
            $c->set("Host", null);
            $c->set("Username", null);
            $c->set("Password", null);
            $c->save();    
        }
    }

    /**
     * @return array
     */
    public static function getMySQLData(): array
    {
        $c = new Config(self::PATH."mysql.yml", Config::YAML);
        $connectionData = [];
        $connectionData['host'] = $c->get("Host");
        $connectionData['user'] = $c->get("Username");
        $connectionData['password'] = $c->get("Password");
        return $connectionData;
    }

    /**
     * @return bool
     */
    public static function isDataOverwritten(): bool
    {
        $c = new Config(self::PATH."mysql.yml", Config::YAML);
        if($c->get("Host") == null | $c->get("Username") == null || $c->get("Password") == null) return false;

        return true;
    }

    /**
     * @return MySQL[]
     */
    public static function getMysqlConnections(): array
    {
        return self::$mysql_connections;
    }

    /**
     * @param string $index
     * @return bool     */
    public static function existConnection(string $index): bool
    {
        return array_key_exists($index, self::$mysql_connections);
    }
    /**
     * @param string $index
     * @return MySQL|null
     */
    public static function getSQLConnection(string $index): ?MySQL
    {
        if(array_key_exists($index, self::$mysql_connections)){
            $connection = self::$mysql_connections[$index];
            if(!mysqli_ping($connection->getSql())) {
                self::removeMySQLConnection($index);
                self::addMySQLConnections($index, new MySQL($connection->getIndex(), $connection->getHost(), $connection->getDatabase(), $connection->getPassword(), $connection->getUsername()));
            }
            return self::$mysql_connections[$index];
        }

        return null;
    }

    /**
     * @param string $index
     * @param MySQL $mySQL
     */
    public static function addMySQLConnections(string $index, MySQL $mySQL): void
    {
        self::$mysql_connections[$index] = $mySQL;
    }

    /**
     * @param string $index
     */
    public static function removeMySQLConnection(string $index)
    {
        unset(self::$mysql_connections[$index]);
    }

    /**
     * OLD FUNCTION OF RUSHEROASE
     * @param string $insert
     * @return bool
     */
    public static function checkInsert(string $insert): bool{
        $filters = [
            "§",
            "$".
            ".",
            ",",
            "-",
            "(",
            ")",
            '"',
            "'",
            ";",
            "'",
            "%",
            "DELETE",
            "INSERT",
            "DROP",
            "SELECT",
            "*"
        ];

        $result = $insert;
        foreach($filters as $filter){
            $result = str_replace($filter, "", $insert);
        }

        return $insert === $result;
    }

    /**
     * @param $class
     */
    public function exec($class)
    {
        Server::getInstance()->getAsyncPool()->submitTask($class);
    }
}