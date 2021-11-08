<?php

namespace ryzerbe\core;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use ReflectionException;
use ryzerbe\core\command\LanguageCommand;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\util\Settings;

class RyZerBE extends PluginBase {

    /** @var RyZerBE  */
    public static RyZerBE $plugin;

    const PREFIX = TextFormat::WHITE.TextFormat::BOLD."RyZer".TextFormat::RED."BE ".TextFormat::RESET;

    public function onEnable(){
        self::$plugin = $this;
        $this->initListener(__DIR__."/listener/");
        $this->initCommands();
        Settings::getInstance()->initMySQL();
        RankManager::getInstance();
        $this->boot();
    }

    public function boot(){
        LanguageProvider::fetchLanguages();
        RankManager::getInstance()->fetchRanks();
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
        $this->getServer()->getCommandMap()->registerAll("core", [
            new LanguageCommand()
        ]);
    }

    /**
     * @return RyZerBE
     */
    public static function getPlugin(): RyZerBE{
        return self::$plugin;
    }
}