<?php

namespace ryzerbe\core;

use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use ReflectionException;
use ryzerbe\core\block\TNTBlock;
use ryzerbe\core\command\BanCommand;
use ryzerbe\core\command\BanHistoryDeleteCommand;
use ryzerbe\core\command\BroadcastCommand;
use ryzerbe\core\command\ClanUiCommand;
use ryzerbe\core\command\CoinCommand;
use ryzerbe\core\command\GamemodeCommand;
use ryzerbe\core\command\GameTimeCommand;
use ryzerbe\core\command\JoinMeCommand;
use ryzerbe\core\command\KickCommand;
use ryzerbe\core\command\LanguageCommand;
use ryzerbe\core\command\LoginCommand;
use ryzerbe\core\command\PartyCommand;
use ryzerbe\core\command\PunishHistoryCommand;
use ryzerbe\core\command\RankCommand;
use ryzerbe\core\command\SettingsCommand;
use ryzerbe\core\command\TeamchatCommand;
use ryzerbe\core\command\UnbanCommand;
use ryzerbe\core\command\VanishCommand;
use ryzerbe\core\command\VerifyCommand;
use ryzerbe\core\command\YouTubeCommand;
use ryzerbe\core\entity\Arrow;
use ryzerbe\core\entity\EnderPearl;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\networklevel\NetworkLevelProvider;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\task\RyZerUpdateTask;
use ryzerbe\core\util\Settings;
use function var_dump;

class RyZerBE extends PluginBase {

    /** @var RyZerBE  */
    public static RyZerBE $plugin;

    const PREFIX = TextFormat::WHITE.TextFormat::BOLD."RyZer".TextFormat::RED."BE ".TextFormat::RESET;

    public function onEnable(){
        self::$plugin = $this;

        $this->initListener(__DIR__."/listener/");
        $this->initCommands();
        $this->initBlocks();
        $this->initEntities();

        Settings::getInstance()->initMySQL();
        RankManager::getInstance();

        $this->boot();
    }

    public function boot(){
        LanguageProvider::fetchLanguages();
        NetworkLevelProvider::initRewards();
        RankManager::getInstance()->fetchRanks();
        StaffProvider::refresh();
        PunishmentProvider::loadReasons();

        $this->getScheduler()->scheduleRepeatingTask(new RyZerUpdateTask(), 1);
    }

    /**
     * @param string $directory
     * @throws ReflectionException
     */
    private function initListener(string $directory): void{
        foreach(scandir($directory) as $listener){
            if($listener === "." || $listener === "..") continue;
            if(is_dir($directory.$listener)){
                $this->initListener($directory.$listener."/");
                continue;
            }
            $dir = str_replace([$this->getFile()."src/", "/"], ["", "\\"], $directory);
            $refClass = new ReflectionClass($dir.str_replace(".php", "", $listener));
            $class = new ($refClass->getName());
            if($class instanceof Listener){
                $this->getServer()->getPluginManager()->registerEvents($class, $this);
                $this->getLogger()->debug("Registered ".$refClass->getShortName()." listener");
            }
        }
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
            new SettingsCommand(),
            new TeamchatCommand(),
            new YouTubeCommand(),
            new BanCommand(),
            new UnbanCommand(),
            new BanHistoryDeleteCommand(),
            new PunishHistoryCommand(),
            new KickCommand(),
            new JoinMeCommand(),
            new LoginCommand(),
            new PartyCommand()
        ]);
    }

    private function initEntities(): void{
        Entity::registerEntity(EnderPearl::class, true, ["minecraft:enderpearl", "Enderpearl"]); // Java Enderpearl
        Entity::registerEntity(Arrow::class, true, ['Arrow', 'minecraft:arrow']); //Bow-Knockback...
    }

    public function initBlocks(): void{
        BlockFactory::registerBlock(new TNTBlock(), true); //TEAM TNT
    }

    /**
     * @return RyZerBE
     */
    public static function getPlugin(): RyZerBE{
        return self::$plugin;
    }
}