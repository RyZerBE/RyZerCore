<?php


namespace baubolp\core\command;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\AsyncExecutor;
use baubolp\core\provider\VanishProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class VanishCommand extends Command
{

    public function __construct()
    {
        parent::__construct("vanish", "", "", ["v"]);
        $this->setPermission("core.vanish");
        $this->setPermissionMessage(TextFormat::RED."No Permission!");
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        $ryzerPlayer = RyzerPlayerProvider::getRyzerPlayer($sender->getName());
        $playerName = $sender->getName();

        if($ryzerPlayer === null) return;

        if(VanishProvider::isVanished($sender->getName())) {
            VanishProvider::vanishPlayer($ryzerPlayer, false);
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Du bist nun wieder sichtbar!");
            AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (\mysqli $mysqli) use ($playerName){
                $mysqli->query("DELETE FROM `vanish` WHERE playername='$playerName'");
            });
        }else {
            VanishProvider::vanishPlayer($ryzerPlayer, true);
            AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (\mysqli $mysqli) use ($playerName){
                $mysqli->query("INSERT INTO `vanish`(`playername`) VALUES ('$playerName')");
            });
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Du bist nicht mehr sichtbar!");
        }
    }
}