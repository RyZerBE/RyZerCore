<?php

namespace ryzerbe\core\anticheat\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ReflectionException;
use ryzerbe\core\anticheat\AntiCheatManager;
use function implode;

class ModuleInfoCommand extends Command {

    public function __construct(){
        parent::__construct("modules", "AntiCheat Modules", "", []);
        $this->setPermission("ryzer.anticheat.modules");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     * @throws ReflectionException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        $modules = [];
        $modules[] = AntiCheatManager::PREFIX.TextFormat::GREEN."My Modules";
        foreach(AntiCheatManager::getModulesIgnoreRegister() as $moduleName => $modulePath){
            $modules[] = TextFormat::DARK_GRAY."» ".TextFormat::GOLD.TextFormat::BOLD.$moduleName.TextFormat::RESET.TextFormat::DARK_GRAY." (".((AntiCheatManager::isCheckRegistered($modulePath) === true) ? TextFormat::GREEN."Activated" : TextFormat::RED."Deactivated").TextFormat::DARK_GRAY.")";
        }

        $sender->sendMessage(implode("\n", $modules));
    }
}