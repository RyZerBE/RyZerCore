<?php

namespace ryzerbe\core;

use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\block\TNTBlock;
use ryzerbe\core\command\BanCommand;
use ryzerbe\core\command\BanHistoryDeleteCommand;
use ryzerbe\core\command\BroadcastCommand;
use ryzerbe\core\command\ChunkFixCommand;
use ryzerbe\core\command\ClanUiCommand;
use ryzerbe\core\command\CoinCommand;
use ryzerbe\core\command\GamemodeCommand;
use ryzerbe\core\command\GameTimeCommand;
use ryzerbe\core\command\JoinMeCommand;
use ryzerbe\core\command\KickCommand;
use ryzerbe\core\command\LanguageCommand;
use ryzerbe\core\command\LoginCommand;
use ryzerbe\core\command\NetworkLevelCommand;
use ryzerbe\core\command\PartyCommand;
use ryzerbe\core\command\PlayerSettingsCommand;
use ryzerbe\core\command\PunishHistoryCommand;
use ryzerbe\core\command\RankCommand;
use ryzerbe\core\command\TeamchatCommand;
use ryzerbe\core\command\UnbanCommand;
use ryzerbe\core\command\UserInfoCommand;
use ryzerbe\core\command\VanishCommand;
use ryzerbe\core\command\VerifyCommand;
use ryzerbe\core\command\YouTubeCommand;
use ryzerbe\core\entity\Arrow;
use ryzerbe\core\entity\EnderPearl;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\networklevel\NetworkLevelProvider;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\provider\VIPJoinProvider;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\task\RyZerUpdateTask;
use ryzerbe\core\util\loader\ListenerDirectoryLoader;
use ryzerbe\core\util\Settings;

class RyZerBE extends PluginBase {
    public const PREFIX = TextFormat::WHITE.TextFormat::BOLD."RyZer".TextFormat::RED."BE ".TextFormat::RESET;

    public static RyZerBE $plugin;

    /**
     * @throws ReflectionException
     */
    public function onEnable(): void{
        self::$plugin = $this;

        ListenerDirectoryLoader::load($this, $this->getFile(), __DIR__ . "/listener/");

        $this->initCommands();
        $this->initBlocks();
        $this->initEntities();

        Settings::getInstance()->initMySQL();
        RankManager::getInstance();

        $this->boot();
    }

    public function boot(): void{
        LanguageProvider::fetchLanguages();
        NetworkLevelProvider::initRewards();
        RankManager::getInstance()->fetchRanks();
        StaffProvider::refresh();
        PunishmentProvider::loadReasons();

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
            new UserInfoCommand()
        ]);
    }

    private function initEntities(): void{
        Entity::registerEntity(EnderPearl::class, true, ["minecraft:enderpearl", "Enderpearl"]); // Java Enderpearl
        Entity::registerEntity(Arrow::class, true, ['Arrow', 'minecraft:arrow']); //Bow-Knockback...
    }

    public function initBlocks(): void{
        BlockFactory::registerBlock(new TNTBlock(), true); //TEAM TNT
    }

    public static function getPlugin(): RyZerBE{
        return self::$plugin;
    }
}