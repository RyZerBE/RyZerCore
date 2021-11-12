<?php

declare(strict_types=1);

namespace ryzerbe\core\util\loader;

use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use ReflectionClass;
use ReflectionException;
use function is_dir;
use function scandir;
use function str_replace;

class ListenerDirectoryLoader {
    /**
     * @throws ReflectionException
     */
    public static function load(Plugin $plugin, string $file, string $directory): void{
        $reflection = new ReflectionClass($plugin);
        $method = $reflection->getMethod("getFile");
        $method->setAccessible(true);
        foreach(scandir($directory) as $listener){
            if($listener === "." || $listener === "..") continue;
            if(is_dir($directory.$listener)){
                self::load($plugin, $file,$directory.$listener."/");
                continue;
            }
            $dir = str_replace([$file."src/", "/"], ["", "\\"], $directory);
            $refClass = new ReflectionClass($dir.str_replace(".php", "", $listener));
            $class = new ($refClass->getName());
            if($class instanceof Listener){
                $plugin->getServer()->getPluginManager()->registerEvents($class, $plugin);
                $plugin->getLogger()->debug("Registered ".$refClass->getShortName()." listener");
            }
        }
    }
}