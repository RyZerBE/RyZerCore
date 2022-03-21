<?php

namespace ryzerbe\core;

use BauboLP\Cloud\Bungee\BungeeAPI;
use BauboLP\Cloud\Provider\CloudProvider;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\block\TNTBlock;
use ryzerbe\core\command\BanCommand;
use ryzerbe\core\command\BanHistoryDeleteCommand;
use ryzerbe\core\command\BanUiCommand;
use ryzerbe\core\command\BroadcastCommand;
use ryzerbe\core\command\ChunkFixCommand;
use ryzerbe\core\command\ClanUiCommand;
use ryzerbe\core\command\CoinBoostCommand;
use ryzerbe\core\command\CoinCommand;
use ryzerbe\core\command\EmojiListCommand;
use ryzerbe\core\command\GamemodeCommand;
use ryzerbe\core\command\GameTimeCommand;
use ryzerbe\core\command\InvBugFixCommand;
use ryzerbe\core\command\JoinMeCommand;
use ryzerbe\core\command\KickCommand;
use ryzerbe\core\command\LanguageCommand;
use ryzerbe\core\command\LoginCommand;
use ryzerbe\core\command\NetworkLevelCommand;
use ryzerbe\core\command\NickCommand;
use ryzerbe\core\command\PartyCommand;
use ryzerbe\core\command\PlayerSettingsCommand;
use ryzerbe\core\command\PunishHistoryCommand;
use ryzerbe\core\command\RankCommand;
use ryzerbe\core\command\ReportCommand;
use ryzerbe\core\command\SetBanCommand;
use ryzerbe\core\command\SkinDatabaseCommand;
use ryzerbe\core\command\TeamchatCommand;
use ryzerbe\core\command\TestCommand;
use ryzerbe\core\command\UnbanCommand;
use ryzerbe\core\command\UpdatelogCommand;
use ryzerbe\core\command\UserInfoCommand;
use ryzerbe\core\command\VanishCommand;
use ryzerbe\core\command\VerifyCommand;
use ryzerbe\core\command\YouTubeCommand;
use ryzerbe\core\entity\Arrow;
use ryzerbe\core\entity\EnderPearl;
use ryzerbe\core\item\bow\PvPBow;
use ryzerbe\core\item\rod\entity\FishingHook;
use ryzerbe\core\item\rod\PvPRod;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\networklevel\NetworkLevelProvider;
use ryzerbe\core\provider\NickProvider;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\provider\VIPJoinProvider;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\task\RyZerUpdateTask;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\WebhookLinks;
use ryzerbe\core\util\loader\ListenerDirectoryLoader;
use ryzerbe\core\util\logger\ErrorLogger;
use ryzerbe\core\util\Settings;

class RyZerBE extends PluginBase {
    public const PREFIX = TextFormat::WHITE.TextFormat::BOLD."RyZer".TextFormat::RED."BE ".TextFormat::RESET;

    public static RyZerBE $plugin;
    public static string $file;

    /**
     * @throws ReflectionException
     */
    public function onEnable(): void{
        self::$plugin = $this;
        self::$file = $this->getFile();
        ListenerDirectoryLoader::load($this, self::$file, __DIR__ . "/listener/");

        $this->initCommands();
        $this->initBlocks();
        $this->initEntities();
        $this->initItems();

        Settings::getInstance()->initMySQL();
        RankManager::getInstance();

        popen('rm -r '.$this->getServer()->getDataPath()."server.log", 'r');
        date_default_timezone_set("Europe/Berlin");
        $this->boot();
        MainLogger::getLogger()->addAttachment(new ErrorLogger());
    }

    public function boot(): void{
        LanguageProvider::fetchLanguages();
        NetworkLevelProvider::initRewards();
        RankManager::getInstance()->fetchRanks();
        StaffProvider::refresh();
        PunishmentProvider::loadReasons();
        NickProvider::fetch();

        $this->getServer()->getPluginManager()->registerEvents(new VIPJoinProvider(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new RyZerUpdateTask(), 1);
    }

    private function initCommands(): void{
        $commands = ["help", "gamemode", "list", "tell", "about", "ping", "playsound", "stopsound", "version", "me", "seed", "title", "defaultgamemode", "spawnpoint", "pardon", "pardon-ip", "plugins", "reload", "save-off", "save-on", "transferserver", "checkperm", "ban", "ban-ip", "makeserver", "makeplugin", "difficulty", "extractplugin", "genplugin", "banlist", "kick", "particle", "say", "kill"];
        $commandMap = $this->getServer()->getCommandMap();
        foreach($commands as $command){
            if($commandMap->getCommand($command) !== null){
                $commandMap->unregister($commandMap->getCommand($command));
            }
        }


        $this->getServer()->getCommandMap()->registerAll("core", [
            new LanguageCommand(),
            new RankCommand(),
            new GameTimeCommand(),
            new VerifyCommand(),
            new VanishCommand(),
            new BroadcastCommand(),
            new ClanUiCommand(),
            new CoinCommand(),
            new GamemodeCommand(),
            new TeamchatCommand(),
            new YouTubeCommand(),
            new BanCommand(),
            new UnbanCommand(),
            new BanHistoryDeleteCommand(),
            new PunishHistoryCommand(),
            new KickCommand(),
            new JoinMeCommand(),
            new LoginCommand(),
            new PartyCommand(),
            new NetworkLevelCommand(),
            new PlayerSettingsCommand(),
            new ChunkFixCommand(),
            new UserInfoCommand(),
            new ReportCommand(),
            new NickCommand(),
            new SkinDatabaseCommand(),
            new UpdatelogCommand(),
            new CoinBoostCommand(),
            new InvBugFixCommand(),
            new BanUiCommand(),
            new EmojiListCommand(),
			new SetBanCommand()
        ]);
    }

    private function initEntities(): void{
        Entity::registerEntity(EnderPearl::class, true, ["minecraft:enderpearl", "Enderpearl"]); // Java Enderpearl
        Entity::registerEntity(Arrow::class, true, ['Arrow', 'minecraft:arrow']); //Bow-Knockback...
        Entity::registerEntity(FishingHook::class, true, ['fishing_hook']);
    }

    public function initBlocks(): void{
        BlockFactory::registerBlock(new TNTBlock(), true); //TEAM TNT
    }

    public function initItems(): void{
        ItemFactory::registerItem(new PvPRod(), true);
        ItemFactory::registerItem(new PvPBow(), true);
    }


    public static function getPlugin(): RyZerBE{
        return self::$plugin;
    }

	/**
	 * Function onDisable
	 * @return void
	 * @priority LOWEST
	 */
	public function onDisable(): void{
		ErrorLogger::$lastCall = microtime(true) + 10;
		foreach (glob("/root/RyzerCloud/servers/".CloudProvider::getServer()."/crashdumps/*") as $crashDumpPath) {
			$content = file_get_contents($crashDumpPath);
			if(empty($content)) continue;

			$message = new DiscordMessage(WebhookLinks::ERROR_LOGGER);
			$message->setMessage("```php\n" . str_split($content, 1950)[0] . "...```");
			$message->send();
			foreach (ErrorLogger::INFO as $infoPlayerName) {
				BungeeAPI::sendMessage(TextFormat::GRAY . "[" . TextFormat::RED . "Error-Logger" . TextFormat::GRAY . "] " . TextFormat::YELLOW . CloudProvider::getServer() . TextFormat::GRAY . " ist crashed", $infoPlayerName);
			}
		}
    }
}