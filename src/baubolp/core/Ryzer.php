<?php


namespace baubolp\core;


use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\command\PKickCommand;
use baubolp\core\provider\CoinProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\ModerationProvider;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\provider\NetworkLevelProvider;
use baubolp\core\provider\NickProvider;
use baubolp\core\provider\RankProvider;
use baubolp\core\provider\ReportProvider;
use baubolp\core\provider\VIPJoinProvider;
use baubolp\core\module\TrollSystem\TrollSystem;
use baubolp\core\entity\Arrow;
use baubolp\core\entity\EnderPearl;
use baubolp\core\entity\TNT;
use BauboLP\NPCSystem\NPCSystem;
use pocketmine\block\BlockFactory;
use pocketmine\entity\DataPropertyManager;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use TobiasDev\DatabaseAPI\Connection;
use TobiasDev\DatabaseAPI\DatabaseAPI;

class Ryzer extends PluginBase
{

    const PREFIX = TextFormat::WHITE.TextFormat::BOLD."RyZer".TextFormat::RED.TextFormat::BOLD."BE ".TextFormat::RESET;
    /** @var Skin */
    public static Skin $backupSkin;
    /** @var array */
    public static array $translations = [];
    /** @var array  */
    public static array $banIds = [];
    /** @var Connection */
    private static Connection $async;
    /** @var Ryzer */
    private static Ryzer $plugin;
    /** @var bool */
    private static bool $reduce;

    /** @var TrollSystem */
    public static TrollSystem $trollSystem;

    public static array $permissions = [
        'languages.edit',
        'languages.reload',
        'core.ban',
        'core.unban',
        'core.log',
        'core.login',
        'core.rperms',
        'core.coins.edit',
        'core.reports',
        'chatlog.use',
        'chatmod.bypass.spam',
        'chatmod.bypass.lastmessage',
        'chatmod.bypass.url',
        'core.joinme',
        'core.kick',
        'core.look',
        'core.look.ip',
        'core.tc',
        'core.togglerank',
        'core.disablevipjoin',
        'core.broadcast',
        "gg.vip".
        "core.gamemode.1",
        "core.gamemode.other",
        "core.gamemode",
        "game.start",
        "bw.start",
        "core.vanish",
        "altay.command.playsound",
        "command.networklevel.use"
    ];

    /** @var MySQLProvider */
    private static MySQLProvider $mysqlProvider;
    /** @var NickProvider */
    private static NickProvider $nickProvider;

    public function onEnable()
    {
        MySQLProvider::createConfig();
        if(!MySQLProvider::isDataOverwritten()) {
            MainLogger::getLogger()->critical(self::PREFIX."MySQL-Data not overwritten! Server stop..");
            sleep(10);
            $this->getServer()->shutdown();
            return;
        }

        self::$plugin = $this;
        self::$mysqlProvider = new MySQLProvider();
        self::setReduce(false);

        $mysqlData = MySQLProvider::getMySQLData();
        self::$async = DatabaseAPI::constructConnection($mysqlData['host'].":3306", $mysqlData['user'], $mysqlData['password']);

        Loader::loadMySQLConnections();
        Loader::loadTables();
        Loader::registerPermissions();
        Loader::unregisterCommands();
        Loader::loadCommands();
        Loader::loadEvents();
        Loader::startTasks();

        NetworkLevelProvider::initRewards();

        self::$backupSkin = new Skin("backup_skin" . rand(128, 100000), NPCSystem::getSkinBytes("backup_skin.png"), "", NPCSystem::$DEFAULT_GEOMETRY_DATA["name"], NPCSystem::$DEFAULT_GEOMETRY_DATA["geo"]);

        Entity::registerEntity(EnderPearl::class, true, ["minecraft:enderpearl", "Enderpearl"]); // Java Enderpearl
        Entity::registerEntity(Arrow::class, true, ['Arrow', 'minecraft:arrow']); //Bow-Knockback...
        BlockFactory::registerBlock(new TNT(), true); //TEAM TNT


        ModerationProvider::loadBanReasons();
        RankProvider::loadRanks();
        ReportProvider::createConfig();
        //RankProvider::addRank("Player", 0); //0 = Standard

        LanguageProvider::createLanguage("Deutsch");
        LanguageProvider::createLanguage("English");
        LanguageProvider::loadLanguages();

        self::$nickProvider = new NickProvider();
        self::$trollSystem = new TrollSystem();
        new VIPJoinProvider();

        $this->getScheduler()->scheduleDelayedTask(new class extends Task{
            public function onRun(int $currentTick)
            {
                if(CloudBridge::getCloudProvider()->isServerPrivate(CloudProvider::getServer()) !== false) {
                    Ryzer::$trollSystem = new TrollSystem();
                    Ryzer::$trollSystem->enable(Ryzer::getPlugin());
                    Server::getInstance()->getCommandMap()->register("pkick", new PKickCommand());
                }
            }
        }, 20 * 10);
        popen('rm -r '.$this->getServer()->getDataPath()."server.log", 'r');
    }

    public function onDisable()
    {
        foreach (MySQLProvider::getMysqlConnections() as $connection)
            $connection->getSql()->close();

       /* foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer)
            BungeeAPI::transfer($onlinePlayer->getName(), self::getRandomLobby());*/
    }

    /**
     * @return Connection
     */
    public static function getAsyncConnection(): Connection
    {
        return self::$async;
    }

    /**
     * @return Ryzer
     */
    public static function getPlugin(): Ryzer
    {
        return self::$plugin;
    }

    /**
     * @return array
     */
    public static function getPermissions(): array
    {
        return self::$permissions;
    }

    /**
     * @param bool $reduce
     */
    public static function setReduce(bool $reduce): void
    {
        self::$reduce = $reduce;
    }


    /**
     * @return bool
     */
    public static function isReduce(): bool
    {
        return self::$reduce;
    }

    /**
     * @return String
     */
    public static function getRandomLobby(): string
    {
        $lobby_array = [];
        foreach (CloudBridge::getCloudProvider()->getRunningServers() as $server) {
            if(stripos($server, "Lobby") !== false) {
                $lobby_array[] = $server;
            }
        }
        if(count($lobby_array) > 0) {
            shuffle($lobby_array);
            $lobby = $lobby_array[0];
        }else {
            $lobby = "Lobby-1";
        }
        return $lobby;
    }

    /**
     * @return MySQLProvider
     */
    public static function getMysqlProvider(): MySQLProvider
    {
        return self::$mysqlProvider;
    }

    /**
     * @param string $Perm
     * @deprecated
     */
    public static function addPermission(string $Perm)
    {
        self::getPlugin()->createPermission($Perm);
    }

    public function createPermission(string $perm)
    {
        if(PermissionManager::getInstance()->getPermission($perm) != null) return;
        PermissionManager::getInstance()->addPermission(new Permission($perm, '', 'op'));
    }

    /**
     * @return NickProvider
     */
    public static function getNickProvider(): NickProvider
    {
        return self::$nickProvider;
    }

    public static function coinBoost(int $coins, int $percent, string $boosterName, array $getCoins)
    {
        foreach ($getCoins as $playerName) {
            if(($p = Server::getInstance()->getPlayer($playerName)) != null) {
                $p->sendMessage("\n\n".Ryzer::PREFIX.LanguageProvider::getMessageContainer('coinboost-vip', $playerName, ['#percent' => $percent, '#booster' => $boosterName]));
            }
            CoinProvider::addCoins($playerName, $coins + (($coins * $percent) / 100));
        }
    }

    /**
     * @param int $entityId
     * @param string $text
     * @param string $title
     * @param Player[] $players
     */
    public static function renameFloatingText(int $entityId, string $text, string $title = "", array $players = []){
        $actorPacket = new SetActorDataPacket();
        $actorPacket->entityRuntimeId = $entityId;

        $dataPropertyManager = new DataPropertyManager();
        if($title == "")
        $dataPropertyManager->setString(Entity::DATA_NAMETAG, $text);
        else
            $dataPropertyManager->setString(Entity::DATA_NAMETAG, $title."\n".$text);

        $actorPacket->metadata = $dataPropertyManager->getAll();
        foreach($players as $player)
            $player->sendDataPacket($actorPacket);
    }

    /**
     * @param int $entityId
     * @param string $text
     * @param string $title
     * @param Player[] $players
     */
    public static function renameEntity(int $entityId, string $text, string $title = "", array $players = []){
        $actorPacket = new SetActorDataPacket();
        $actorPacket->entityRuntimeId = $entityId;

        $dataPropertyManager = new DataPropertyManager();
        if($title == "")
        $dataPropertyManager->setString(Entity::DATA_NAMETAG, $text);
        else
            $dataPropertyManager->setString(Entity::DATA_NAMETAG, $title."\n".$text);

        $actorPacket->metadata = $dataPropertyManager->getAll();
        foreach($players as $player)
            $player->sendDataPacket($actorPacket);
    }


    /**
     * @return TrollSystem
     */
    public static function getTrollSystem(): TrollSystem
    {
        return self::$trollSystem;
    }

    /**
     * @param string $playerName
     * @return string
     */
    public static function getHeadURL(string $playerName): string
    {
        return "https://api.test.cosmeticsbe.net/api/skin/head/".$playerName;
    }
}