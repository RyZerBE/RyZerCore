<?php


namespace baubolp\core\provider;


use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use baubolp\core\task\GameTimeTop5AsyncTask;
use Closure;
use pocketmine\Server;

class GameTimeProvider
{

    public static function getGameTime(string $username, Closure $closure, ...$extra_data)
    {
        Ryzer::getAsyncConnection()->executeQuery("SELECT gametime FROM GameTime WHERE playername='$username'", "RyzerCore", function (\mysqli_result $result) {
            if ($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    return $data['gametime'];
                }
            }
            return "now";
        }, function ($r, $e) {
        }, $extra_data, $closure);
    }

    public static function convertPlayTimeToGameTime(string $username, bool $array = true)
    {
        if (($p = RyzerPlayerProvider::getRyzerPlayer($username)) != null) {
            $date = $p->getTime();
            $now = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
            $h = $now->diff($date)->h;
            $i = $now->diff($date)->i;

            $string = "$h:$i";
            if ($array) {
                return explode(":", $string);
            } else {
                return $string;
            }
        }

        return null;
    }

    /**
     * @param string $username
     */
    public static function addGameTime(string $username)
    {
        //0 = Hours : 1 = Minutes
        if(stripos(CloudProvider::getServer(), "Lobby") !== false) return;

        $i = self::convertPlayTimeToGameTime($username);

        self::getGameTime($username, function (Server $server, string $result, $extra_data) use ($i, $username) {
            $new_hours = $i[0];
            $ne_minutes = $i[1];
            $oi = explode(":", $result);
            if (isset($oi[0]) && isset($oi[1])) {
                $old_hours = $oi[0];
                $old_minutes = $oi[1];

                $m = $old_minutes + $ne_minutes;

                if ($m >= 60) {
                    $m = $m - 60;
                    $new_hours = $new_hours + 1;
                }

                $new_time_string = $new_hours + $old_hours . ":" . $m;
                MySQLProvider::getSQLConnection("Core")->getSql()->query("UPDATE GameTime SET `gametime`='$new_time_string' WHERE playername='$username'");
            }
        }, ...[]);
    }

    /**
     * @param string $userName
     */
    public static function loadTop5Hologram(string $userName)
    {
        Server::getInstance()->getAsyncPool()->submitTask(new GameTimeTop5AsyncTask($userName));
    }
}