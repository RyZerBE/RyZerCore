<?php


namespace baubolp\core;

use baubolp\core\command\ApplyCommand;
use baubolp\core\command\BroadcastCommand;
use baubolp\core\command\DeactivateVipJoinCommand;
use baubolp\core\command\GamemodeCommand;
use baubolp\core\command\ToggleRankCommand;
use baubolp\core\command\BanCommand;
use baubolp\core\command\ClanUICommand;
use baubolp\core\command\CoinCommand;
use baubolp\core\command\GameTimeCommand;
use baubolp\core\command\JoinMeCommand;
use baubolp\core\command\KickCommand;
use baubolp\core\command\LanguageCommand;
use baubolp\core\command\ListCommand;
use baubolp\core\command\LogCommand;
use baubolp\core\command\LoginCommand;
use baubolp\core\command\LookCommand;
use baubolp\core\command\NickCommand;
use baubolp\core\command\ParticleModCommand;
use baubolp\core\command\ReportCommand;
use baubolp\core\command\RyzerPermsCommand;
use baubolp\core\command\TeamChatCommand;
use baubolp\core\command\UnbanCommand;
use baubolp\core\command\VanishCommand;
use baubolp\core\command\VerifyCommand;
use baubolp\core\command\WarnCommand;
use baubolp\core\command\WebInterfaceCommand;
use baubolp\core\command\YouTubeCommand;
use baubolp\core\listener\BowHitEntityListener;
use baubolp\core\listener\CommandListener;
use baubolp\core\listener\DamageListener;
use baubolp\core\listener\DataPacketReceiveListener;
use baubolp\core\listener\EditionFakerListener;
use baubolp\core\listener\ExplosionPrimeListener;
use baubolp\core\listener\PlayerChatListener;
use baubolp\core\listener\PlayerCreationListener;
use baubolp\core\listener\PlayerJoinListener;
use baubolp\core\listener\PlayerJoinNetworkListener;
use baubolp\core\listener\PlayerQuitListener;
use baubolp\core\listener\PlayerQuitNetworkListener;
use baubolp\core\listener\PlayerRegisterListener;
use baubolp\core\listener\SwitchesFixListener;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\task\AutoMessageTask;
use baubolp\core\task\DelayTask;
use baubolp\core\task\StaffAsyncTask;
use baubolp\core\task\UnblockIpTask;
use baubolp\core\util\MySQL;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

class Loader
{

    public static function loadEvents(): void
    {
        $events = [
            new DataPacketReceiveListener(),
            new PlayerJoinListener(),
            new PlayerQuitListener(),
            new PlayerRegisterListener(),
            new PlayerChatListener(),
            new SwitchesFixListener(),
            new PlayerCreationListener(),
            new CommandListener(),
            new DamageListener(),
            new BowHitEntityListener(),
            new ExplosionPrimeListener(),
            new PlayerJoinNetworkListener(),
            new PlayerQuitNetworkListener(),
            new EditionFakerListener()
        ];


        foreach ($events as $event)
            Server::getInstance()->getPluginManager()->registerEvents($event, Ryzer::getPlugin());

        MainLogger::getLogger()->info(Ryzer::PREFIX . "Events loaded!");
    }

    public static function loadCommands(): void
    {
        $commands = [
            'language' => new LanguageCommand(),
            'ban' => new BanCommand(),
            'unban' => new UnbanCommand(),
            'log' => new LogCommand(),
            'warn' => new WarnCommand(),
            'rperms' => new RyzerPermsCommand(),
            'coins' => new CoinCommand(),
            'report' => new ReportCommand(),
            'login' => new LoginCommand(),
            'verify' => new VerifyCommand(),
            'particlemod' => new ParticleModCommand(),
            'joinme' => new JoinMeCommand(),
            'nick' => new NickCommand(),
            'yt' => new YouTubeCommand(),
            'apply' => new ApplyCommand(),
            'cui' => new ClanUICommand(),
            'kick' => new KickCommand(),
            'look' => new LookCommand(),
            'gametime' => new GameTimeCommand(),
            'teamchat' => new TeamChatCommand(),
            'togglerank' => new ToggleRankCommand(),
            'vipJoin' => new DeactivateVipJoinCommand(),
            'broadcast' => new BroadcastCommand(),
            'cp' => new WebInterfaceCommand(),
            "gamemode" => new GamemodeCommand(),
            "vanish" => new VanishCommand()
        ];

        foreach (array_keys($commands) as $key) {
            Server::getInstance()->getCommandMap()->register($key, $commands[$key]);
        }
        MainLogger::getLogger()->info(Ryzer::PREFIX . "Commands loaded!");
    }

    public static function loadDataBases(): void
    {
        $dataBases = [
            'RyzerCore',
            'Languages'
        ];

        foreach ($dataBases as $dataBase) {
            Ryzer::getAsyncConnection()->execute("CREATE IF NOT EXIST $dataBase DATABASE", "RyzerCore", null);
        }
    }

    public static function loadTables(): void
    {
        Server::getInstance()->getAsyncPool()->submitTask(new class(MySQLProvider::getMySQLData()) extends AsyncTask {

            private $mysqlData;

            public function __construct(array $mysqlData)
            {
                $this->mysqlData = $mysqlData;
            }

            public function onRun()
            {
                $mysqli = new \mysqli($this->mysqlData['host'] . ":3306", $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $mysqli2 = new \mysqli($this->mysqlData['host'] . ":3306", $this->mysqlData['user'], $this->mysqlData['password'], 'ChatLog');
                $mysqli3 = new \mysqli($this->mysqlData['host'] . ":3306", $this->mysqlData['user'], $this->mysqlData['password'], 'MLGRush');
                $mysqli4 = new \mysqli($this->mysqlData['host'] . ":3306", $this->mysqlData['user'], $this->mysqlData['password'], 'GunGame');
                $mysqli5 = new \mysqli($this->mysqlData['host'] . ":3306", $this->mysqlData['user'], $this->mysqlData['password'], 'Webinterface');
                $mysqli->query("CREATE TABLE IF NOT EXISTS BanReasons(id INTEGER NOT NULL KEY AUTO_INCREMENT, reason varchar(64) NOT NULL, type varchar(32) NOT NULL, duration varchar(64) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS replayList(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(16) NOT NULL, replays TEXT NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS vanish(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(16) NOT NULL)");

		$mysqli->query("CREATE TABLE IF NOT EXISTS Friends(id INTEGER NOT NULL KEY AUTO_INCREMENT, player varchar(16) NOT NULL, friends TEXT NOT NULL, friendRequests TEXT NOT NULL, allowFriendRequests int(11) NOT NULL, autoRequestAccept int(11) NOT NULL, onlineStatusMessage int(11) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS MonthlyMissions(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL, mission1 varchar(64) NOT NULL, mission2 varchar(32) NOT NULL, mission3 varchar(64) NOT NULL, mission4 varchar(64) NOT NULL, mission5 varchar(64) NOT NULL, state varchar(16) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS JoinMe(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(32) NOT NULL, server varchar(64) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS Staffs(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(32) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS Coins(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL, coins varchar(32) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS Nick(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL, nick varchar(32) NOT NULL, skin TEXT NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS ToggleRank(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS GameTime(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(32) NOT NULL, gametime varchar(128) NOT NULL)");
                $mysqli2->query("CREATE TABLE IF NOT EXISTS ChatLogs(id varchar(16) NOT NULL, log TEXT NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS Ranks(id INTEGER NOT NULL KEY AUTO_INCREMENT, rankname varchar(64) NOT NULL, nametag varchar(64) NOT NULL, chatprefix varchar(64) NOT NULL, permissions TEXT NOT NULL, joinpower integer NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS PlayerModeration(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL, ban varchar(32), banduration varchar(64), mute varchar(64), muteduration varchar(64), unbanlog TEXT NOT NULL, warns TEXT, log TEXT, banpoints varchar(16) NOT NULL, mutepoints varchar(16) NOT NULL, banid varchar(16), muteid varchar(16))");
                $mysqli->query("CREATE TABLE IF NOT EXISTS PlayerPerms(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL, rankname TEXT NOT NULL, permissions TEXT NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS PlayerLanguage(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL, selected_language varchar(32) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS PlayerData(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(64) NOT NULL, ip varchar(64) NOT NULL, clientid TEXT NOT NULL, clientmodel TEXT NOT NULL, mcid TEXT NOT NULL, accounts TEXT NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS Verify(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(32) NOT NULL, token varchar(16) NOT NULL, isverified varchar(64) NOT NULL)");
                $mysqli->query("CREATE TABLE IF NOT EXISTS ParticleMod(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(32) NOT NULL, pm bool NOT NULL)");
                $mysqli3->query("CREATE TABLE IF NOT EXISTS InvSort(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(32) NOT NULL, invsort varchar(64) NOT NULL, swordinvsort varchar(64) NOT NULL)");
                $mysqli5->query("CREATE TABLE IF NOT EXISTS login(id INTEGER NOT NULL KEY AUTO_INCREMENT, username varchar(16) NOT NULL, password TEXT NOT NULL)");
                $mysqli->close();
                $mysqli2->close();
                $mysqli3->close();
                $mysqli4->query("CREATE TABLE IF NOT EXISTS Stats(id INTEGER NOT NULL KEY AUTO_INCREMENT, playername varchar(16) NOT NULL, bestlevel integer NOT NULL, bestkillstreak integer NOT NULL)");
                $mysqli4->close();
                $mysqli5->close();
            }

            public function onCompletion(Server $server)
            {
                MainLogger::getLogger()->info(Ryzer::PREFIX . "Tables created!");
            }
        });
    }

    public static function loadMySQLConnections(): void
    {
        $mysqlData = MySQLProvider::getMySQLData();
        $connections = [
            new MySQL("Language", $mysqlData['host'] . ":3306", "Languages", $mysqlData['password'], $mysqlData['user']),
            new MySQL("Clans", $mysqlData['host'] . ":3306", "Clans", $mysqlData['password'], $mysqlData['user']),
            new MySQL("Core", $mysqlData['host'] . ":3306", "RyzerCore", $mysqlData['password'], $mysqlData['user']),
            new MySQL("Lobby", $mysqlData['host'] . ":3306", "Lobby", $mysqlData['password'], $mysqlData['user']),
            new MySQL("GunGame", $mysqlData['host'] . ":3306", "GunGame", $mysqlData['password'], $mysqlData['user'])
        ];

        foreach ($connections as $connection) {
            if ($connection instanceof MySQL)
                $connection->register();
        }
        MainLogger::getLogger()->info(Ryzer::PREFIX . "Connections created!");
    }

    public static function unregisterCommands()
    {
        $deaktivatecmd = [
            'msg',
            'pardon',
            'plugins',
            'about',
            'ban-ip',
            'banlist',
            'defaultgamemode',
            'enchant',
            'particle',
            'spawnpoint',
            'kill',
            'help',
            'me',
            'ban',
            'unban',
            'unban-ip',
            'list',
            'playsound',
            'say',
            'msg',
            'kick',
            'gamemode'
        ];
        $commandMap = Server::getInstance()->getCommandMap();
        foreach ($deaktivatecmd as $command) {
            $command = $commandMap->getCommand($command);
            if ($command != null) {
                $commandMap->unregister($command);
            }
        }
        MainLogger::getLogger()->info(Ryzer::PREFIX . "Commands unregistered!");
    }

    public static function registerPermissions()
    {
        foreach (Ryzer::getPermissions() as $permission) {
            $perm = new Permission($permission, '', 'op');
            PermissionManager::getInstance()->addPermission($perm);
        }
        MainLogger::getLogger()->info(Ryzer::PREFIX . "Permissions registered!");
    }

    public static function startTasks()
    {
        Ryzer::getPlugin()->getScheduler()->scheduleRepeatingTask(new UnblockIpTask(), 1);
        Ryzer::getPlugin()->getScheduler()->scheduleRepeatingTask(new DelayTask(), 1);
        Ryzer::getPlugin()->getScheduler()->scheduleRepeatingTask(new AutoMessageTask(), 20 * (60 * 5));

        Ryzer::getPlugin()->getScheduler()->scheduleRepeatingTask(new class extends Task {
            public function onRun(int $currentTick)
            {
                Server::getInstance()->getAsyncPool()->submitTask(new StaffAsyncTask(MySQLProvider::getMySQLData()));
            }
        }, 100);
    }
}