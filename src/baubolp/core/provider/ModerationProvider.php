<?php


namespace baubolp\core\provider;


use BauboLP\Cloud\Bungee\BungeeAPI;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use baubolp\core\util\Webhooks;
use DateInterval;
use DateTimeZone;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

class ModerationProvider
{

    public static function loadBanReasons()
    {
        Ryzer::getAsyncConnection()->executeQuery("SELECT * FROM BanReasons", "RyzerCore", function (\mysqli_result $result) {
            $banReasons = [];
            $id = 0;
            if ($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    $id++;
                    $banReasons[$id] = ['banreason' => $data['reason'], 'type' => $data['type'], 'duration' => $data['duration']];
                }
            }
            return $banReasons;
        }, function ($r, $e) {
        }, [], function (Server $server, array $banReasons, $extra_data) {
            $i = 0;
            foreach ($banReasons as $id => $banData) {
                Ryzer::$banIds[$id] = $banData;
                $i++;
            }
        });
    }

    /**
     * @param $sender
     */
    public static function sendBanIDList($sender)
    {
        if ($sender instanceof Player || $sender instanceof ConsoleCommandSender) {
            foreach (Ryzer::$banIds as $banId => $banData) {
                if ($banData['type'] == 1) {
                    $type = TextFormat::DARK_RED . "BAN";
                } else {
                    $type = TextFormat::DARK_RED . "MUTE";
                }


                if ($banData['duration'] != "Permanent") {
                    $ex = explode(":", $banData['duration']);
                    if ($ex[1] == "H") {
                        $timeIndex = " STUNDEN";
                    } else {
                        $timeIndex = " TAGE";
                    }
                    $duration = (int)$ex[0];
                } else {
                    $timeIndex = "Permanent";
                    $duration = "";
                }


                $sender->sendMessage(Ryzer::PREFIX . TextFormat::DARK_GREEN . $banId . TextFormat::DARK_GRAY . " => " . TextFormat::RED . $banData['banreason'] . TextFormat::DARK_GRAY . " | " . TextFormat::DARK_RED . $type . TextFormat::GRAY . " (" . TextFormat::RED . $duration . TextFormat::DARK_RED . $timeIndex . TextFormat::GRAY . ")");
            }
        }
    }

    /**
     * @param string $playerName
     * @param string $endOfBan
     * @param string $reason
     * @param string $banid
     * @param $staff
     * @param bool $isBan
     */
    public static function addBanToLog(string $playerName, string $endOfBan, string $reason, string $banid, $staff, $isBan = true)
    {
        Ryzer::getAsyncConnection()->executeQuery("SELECT log From PlayerModeration WHERE playername='$playerName'", "RyzerCore", function (\mysqli_result $mysqli_result) use ($playerName, $reason, $staff, $endOfBan) {

            if ($mysqli_result->num_rows > 0) {
                while ($data = $mysqli_result->fetch_assoc()) {
                    return $data['log'];
                }
            }
            return "";
        }, function ($r, $e) {
        }, [], function (Server $server, string $log, $extra_data) use ($reason, $staff, $playerName, $endOfBan, $banid) {
            iF ($log == "") {
                $newLog = "$reason,$staff,$endOfBan,$banid";
            } else {
                $newLog = $log . "*$reason,$staff,$endOfBan,$banid";
            }
            Ryzer::getAsyncConnection()->execute("UPDATE PlayerModeration SET log='$newLog' WHERE playername='$playerName'", 'RyzerCore', null, []);
        });
    }

    /**
     * @param string $playerName
     * @param bool $ban
     * @return int
     */
    public static function getPoints(string $playerName, bool $ban = true): int
    {
        $result = MySQLProvider::getSQLConnection("Core")->getSql()->query("SELECT banpoints,mutepoints FROM PlayerModeration WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            while ($data = $result->fetch_assoc()) {
                if ($ban) {
                    return $data['banpoints'];
                } else {
                    return $data['mutepoints'];
                }
            }
        }
        return 1;
    }

    /**
     * @param string $duration
     * @return string
     */
    public static function formatGermanDate(string $duration)
    {
        if($duration == "Permanent") return "Permanent";

        $split = explode(" ", $duration);
        $year = explode("-", $split[0]);
        $clock = explode(":", $split[1]);
        return $year[2] . "." . $year[1] . "." . $year[0] . " um " . $clock[0] . ":" . $clock[1];
    }

    /**
     * @param string $reason
     * @param string $durationFormat
     * @param string $banId
     * @param bool $english
     * @return string
     */
    public static function getBanScreen(string $reason, string $durationFormat, string $banId, bool $english = true): string
    {
        if ($english) {
            $banned = TextFormat::RED . "Your " . TextFormat::AQUA . "Account " . TextFormat::RED . "has been " . TextFormat::AQUA . "SUSPENDED" . TextFormat::RED . " from the network!\n"
                . TextFormat::RED . "Reason: " . TextFormat::AQUA . $reason . TextFormat::RED . " ID: " . TextFormat::AQUA . $banId . "\n" .
                TextFormat::RED . "Until: " . TextFormat::AQUA . $durationFormat . "\n" . TextFormat::YELLOW . "discord.ryzer.be";
        } else {
            $banned = TextFormat::RED . "Dein " . TextFormat::AQUA . "Account " . TextFormat::RED . "wurde vom Netzwerk " . TextFormat::AQUA . "AUSGESCHLOSSEN" . TextFormat::RED . "!\n"
                . TextFormat::RED . "Grund: " . TextFormat::AQUA . $reason . TextFormat::RED . " Deine ID: " . TextFormat::AQUA . $banId . "\n" .
                TextFormat::RED . "Ende: " . TextFormat::AQUA . $durationFormat . "\n" . TextFormat::YELLOW . "discord.ryzer.be";
        }
        return $banned;
    }

    /**
     * @return string
     */
    public static function generateBanId(): string
    {
        $string = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789987654321YxCvBnMlKjHgFdSaQwErTzUiOlP";
        $id = "";
        for ($i = 0; 8 > $i; $i++) {
            $id .= $string[rand(0, strlen($string) - 1)];
        }
        return $id;
    }

    /**
     * @param string $id
     */
    public static function createProof(string $id) {
        Ryzer::getMysqlProvider()->exec(new class($id) extends AsyncTask{

            private $id;
            private $mysqlData;

            public function __construct(string $id)
            {
                $this->id = $id;
                $this->mysqlData = MySQLProvider::getMySQLData();
            }

            public function onRun()
            {
                $id = $this->id;
                $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $mysqli->query("INSERT INTO `Proofs`(`id`, `mid`) VALUES ('$id', '')");
                $mysqli->close();
            }
        });
    }

    public static function setBan(string $playerName, array $banData, int $banPoints, string $banid, string $staff)
    {
        $reason = $banData['banreason'];
        if ($banData['duration'] != "Permanent") {
            if ($banPoints < 5) {
                $ex = explode(":", $banData['duration']);

                $now = new \DateTime('now', new DateTimeZone('Europe/Berlin'));
                $duration = (int)$ex[0];
                if ($ex[1] == "H") {
                    $now->add(new DateInterval('PT' . $banPoints * $duration . "H"));
                } else {
                    $now->add(new DateInterval('P' . $banPoints * $duration . "D"));
                }
                $duration = $now->format('Y-m-d H:i:s');
            } else {
                $duration = "Permanent";
            }

        } else {
            $duration = "Permanent";
        }

        Ryzer::getAsyncConnection()->execute("UPDATE PlayerModeration SET banpoints=banpoints+1,ban='$reason',banduration='$duration',banid='$banid' WHERE playername='$playerName'", 'RyzerCore', null, []);
        self::addBanToLog($playerName, $duration, $reason, $banid, $staff);
        if ($duration != "Permanent") {
            $format = self::formatGermanDate($duration);
        } else {
            $format = $duration;
        }
        BungeeAPI::kickPlayer($playerName, self::getBanScreen($reason, $format, $banid, LanguageProvider::getLanguage($playerName) == "English"));
        DiscordProvider::sendEmbedMessageToDiscord(Webhooks::STRAFLOG, "Spion",
            [['name' => "Spieler", 'value' => $playerName, 'inline' => false],
            ['name' => "Grund", 'value' => $reason, 'inline' => false],
            ['name' => "Gebannt bis", 'value' => ModerationProvider::formatGermanDate($duration), 'inline' => false],
            ['name' => "Staff", 'value' => $staff, 'inline' => false],
            ['name' => "ID", 'value' => $banid, 'inline' => false]], ['text' => "RyZerBE", 'icon_url' => Webhooks::ICON], null, "Es wurde ein Spieler gebannt", "");
    }

    /**
     * @param string $playerName
     * @param array $banData
     * @param int $mutePoints
     * @param string $banid
     * @param string $staff
     * @throws \Exception
     */
    public static function setMute(string $playerName, array $banData, int $mutePoints, string $banid, string $staff)
    {
        $reason = $banData['banreason'];
        if ($banData['duration'] != "Permanent") {
            if ($mutePoints < 5) {
                $ex = explode(":", $banData['duration']);

                $now = new \DateTime('now', new DateTimeZone('Europe/Berlin'));
                $duration = (int)$ex[0];
                if ($ex[1] == "H") {
                    $now->add(new DateInterval('PT' . $mutePoints * $duration . "H"));
                } else {
                    $now->add(new DateInterval('P' . $mutePoints * $duration . "D"));
                }
                $duration = $now->format('Y-m-d H:i:s');
            } else {
                $duration = "Permanent";
            }
        } else {
            $duration = "Permanent";
        }

        Ryzer::getAsyncConnection()->execute("UPDATE PlayerModeration SET mutepoints=mutepoints+1,mute='$reason',muteduration='$duration',muteid='$banid' WHERE playername='$playerName'", 'RyzerCore', null, []);
        self::addBanToLog($playerName, $duration, $reason, $banid, $staff);
        DiscordProvider::sendEmbedMessageToDiscord(Webhooks::STRAFLOG, "Spion",
            [['name' => "Spieler", 'value' => $playerName, 'inline' => false],
                ['name' => "Grund", 'value' => $reason, 'inline' => false],
                ['name' => "Gemutet bis", 'value' => ModerationProvider::formatGermanDate($duration), 'inline' => false],
                ['name' => "Staff", 'value' => $staff, 'inline' => false],
                ['name' => "ID", 'value' => $banid, 'inline' => false]], ['text' => "RyZerBE", 'icon_url' => Webhooks::ICON], null, "Es wurde ein Spieler gemutet", "");

        if (($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
            $obj->setMuted(true);
            $obj->setMuteData(['reason' => $banData['banreason'], 'duration' => $duration, 'id' => $banid]);
        }
    }

    /**
     * @param string $playerName
     */
    public static function unban(string $playerName)
    {
        Ryzer::getAsyncConnection()->execute("UPDATE PlayerModeration SET ban='',banduration='',banid='' WHERE playername='$playerName'", 'RyzerCore', null, []);

    }

    /**
     * @param string $playerName
     */
    public static function unmute(string $playerName)
    {
        if (($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
            $obj->setMuted(false);
        }
        Ryzer::getAsyncConnection()->execute("UPDATE PlayerModeration SET mute='',muteduration='',muteid='' WHERE playername='$playerName'", 'RyzerCore', null, []);
    }

    public static function addWarn(string $playerName, string $sender, string $reason)
    {
        $now = new \DateTime('now', new DateTimeZone('Europe/Berlin'));
        $format = $now->format('Y-m-d H:i:s');

        Ryzer::getAsyncConnection()->executeQuery("SELECT warns FROM PlayerModeration WHERE playername='$playerName'", 'RyzerCore', function (\mysqli_result $result) use ($playerName, $reason, $format, $sender) {
            if ($result->num_rows > 0) {
                $newWarLog = "";
                while ($data = $result->fetch_assoc()) {
                    if ($data['warns'] == "") {
                        $newWarLog = "$reason,$sender,$format";
                    } else {
                        $newWarLog = $data['warns'] . "*" . "$reason,$sender,$format";
                    }
                }
                return $newWarLog;
            }
            return "";
        }, function ($r, $e) {
        }, [], function (Server $server, string $log, $extra_data) use ($playerName) {
            Ryzer::getAsyncConnection()->execute("UPDATE PlayerModeration SET warns='$log' WHERE playername='$playerName'", 'RyzerCore', null);
        });
        DiscordProvider::sendEmbedMessageToDiscord(Webhooks::STRAFLOG, "Spion",
            [['name' => "Spieler", 'value' => $playerName, 'inline' => false],
                ['name' => "Grund", 'value' => $reason, 'inline' => false],
                ['name' => "Staff", 'value' => $sender, 'inline' => false]], ['text' => "RyZerBE", 'icon_url' => Webhooks::ICON], null, "Es wurde ein Spieler verwarnt", "");
    }

    public static function addUnbanLog(string $playerName, string $reason, string $format, string $sender, bool $ban)
    {
        Ryzer::getAsyncConnection()->executeQuery("SELECT unbanlog FROM PlayerModeration WHERE playername='$playerName'", 'RyzerCore', function (\mysqli_result $result) use ($playerName, $reason, $sender, $format, $ban) {
            if ($result->num_rows > 0) {
                $newWarLog = "";
                while ($data = $result->fetch_assoc()) {
                    if ($data['unbanlog'] == "") {
                        $newWarLog = "$reason,$sender,$format,$ban";
                    } else {
                        $newWarLog = $data['unbanlog'] . "*" . "$reason,$sender,$format,$ban";
                    }
                }
                return $newWarLog;
            }
            return "";
        }, function ($r, $e) {
        }, [], function (Server $server, string $log, $extra_data) use ($playerName) {
            Ryzer::getAsyncConnection()->execute("UPDATE PlayerModeration SET unbanlog='$log' WHERE playername='$playerName'", 'RyzerCore', null);
        });
        DiscordProvider::sendEmbedMessageToDiscord(Webhooks::STRAFLOG, "Spion",
            [['name' => "Spieler", 'value' => $playerName, 'inline' => false],
                ['name' => "Grund", 'value' => $reason, 'inline' => false],
                ['name' => "Staff", 'value' => $sender, 'inline' => false]], ['text' => "RyZerBE", 'icon_url' => Webhooks::ICON], null, ($ban == true) ? "Es wurde ein Spieler entbannt" : "Es wurde ein Spieler entmutet", "");
    }

    /**
     * @param string $message
     */
    public static function broadcastMessageToStaffs(string $message)
    {
        StaffProvider::sendMessageToStaffs($message);
    }

    /**
     * @param string $address
     * @return string
     */
    public static function hideAddress(string $address): string
    {
        $i = explode(".", $address);
        if (isset($i[2]) && isset($i[3])) {
            return "xx.xx." . $i[2] .".". $i[3];
        }
        return "xx.xx.xx.xx";
    }
}