<?php


namespace baubolp\core\provider;



use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use baubolp\core\util\Webhooks;
use DateTime;
use DateTimeZone;
use Exception;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class ReportProvider
{

    const PATH = "/root/RyzerCloud/data/reports.json";

    public static function createConfig()
    {
        if(!is_file(self::PATH)) {
            $c = new Config(self::PATH, Config::JSON);
            $c->set("Reports", []);
            $c->set("Archiv", []);
            $c->save();
        }
    }

    /**
     * @return array
     */
    public static function getReports(): array
    {
        $c = new Config(self::PATH);
        return $c->get('Reports');
    }

    /**
     * @param string $badPlayer
     * @param string $senderName
     * @param string $reason
     * @throws Exception
     */
    public static function addReport(string $badPlayer, string $senderName, string $reason)
    {
        $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        $time = $now->format('Y-m-d H:i:s');
        $c = new Config(self::PATH);
        $reports = $c->get('Reports');
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($badPlayer)) != null) {
            $reports[$badPlayer] = ['badPlayer' => $badPlayer, 'sender' => $senderName, 'reason' => $reason, 'server' => CloudProvider::getServer(), 'time' => $time, 'deviceId' => $obj->getLoginData()->getDeviceId(), 'ip' => $obj->getLoginData()->getAddress()];
        }else {
            $reports[$badPlayer] = ['badPlayer' => $badPlayer, 'sender' => $senderName, 'reason' => $reason, 'server' => CloudProvider::getServer(), 'time' => $time];
        }
        $c->set('Reports', $reports);
        $c->save();
        StaffProvider::sendMessageToStaffs("\n\n\n\n".Ryzer::PREFIX.TextFormat::GRAY."Der Spieler ".TextFormat::AQUA.$badPlayer.TextFormat::GRAY." wurde gemeldet."."\n".TextFormat::GRAY."Grund: ".TextFormat::YELLOW.$reason."\n".TextFormat::GRAY."Server: ".TextFormat::YELLOW.CloudProvider::getServer()."\n".TextFormat::GRAY."Melder: ".TextFormat::YELLOW.$senderName);
        DiscordProvider::sendEmbedMessageToDiscord(Webhooks::REPORT_LOG, "Spion",
            [['name' => "Spieler", 'value' => $badPlayer, 'inline' => false],
            ['name' => 'Melder', 'value' => $senderName, 'inline' => false],
            ['name' => "Grund", 'value' => $reason, 'inline' => false],
            ['name' => "Server", 'value' => CloudProvider::getServer(), 'inline' => false]], ['text' => "RyZerBE", 'icon_url' => Webhooks::ICON], null, "Ein Spieler wurde verdächtig");
        DiscordProvider::sendMessageToDiscord("Spion", "@Staff", Webhooks::REPORT_LOG);
    }

    /**
     * @param string $badPlayer
     */
    public static function removeReport(string $badPlayer)
    {
        $c = new Config(self::PATH);
        $reports = $c->get('Reports');
        unset($reports[$badPlayer]);
        $c->set('Reports', $reports);
        $c->save();
    }

    /**
     * @param string $badPlayer
     * @param string $senderName
     * @param string $reason
     * @param bool $accepted
     * @param string $staff
     * @param string $server
     * @param string $deviceId
     * @param string $ip
     * @throws Exception
     */
    public static function addReportToArchiv(string $badPlayer, string $senderName, string $reason, bool $accepted, string $staff, string $server, string $deviceId, string $ip)
    {
        $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        $time = $now->format('Y-m-d H:i:s');
        $c = new Config(self::PATH);
        $archiv = $c->get('Archiv');
        $archiv[] = ['badPlayer' => $badPlayer, 'sender' => $senderName, 'reason' => $reason, 'accepted' => $accepted, 'staff' => $staff, 'time' => $time, 'deviceId' => $deviceId, 'ip' => $ip, 'server' => $server];
        $c->set("Archiv", $archiv);
        $c->save();
    }

    /**
     * @param string $badPlayer
     * @return bool
     */
    public static function existReport(string $badPlayer): bool
    {
        $c = new Config(self::PATH);
        return isset($c->get('Reports')[$badPlayer]);
    }

    /**
     * @param string $badPlayer
     * @return array|null
     */
    public static function getReportInformation(string $badPlayer): ?array
    {
        if(!self::existReport($badPlayer)) return null;
        $c = new Config(self::PATH);
        return $c->get('Reports')[$badPlayer];
    }

    /**
     * @return array
     */
    public static function getArchive(): array
    {
        $c = new Config(self::PATH);
        return $c->get('Archiv');
    }
}