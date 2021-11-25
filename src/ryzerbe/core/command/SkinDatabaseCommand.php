<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\skin\SkinDatabase;

class SkinDatabaseCommand extends Command {

    public function __construct(){
        parent::__construct("skindatabase", "RyZerBE Skindatabase Command", "", ["skindb"]);
        $this->setPermission("ryzer.admin");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;
        if(!$this->testPermission($sender)) return;

        if(empty($args[0]) || empty($args[1])) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."/skindb load <Name> [version]");
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."/skindb save <Name> <version>");
            return;
        }

        switch($args[0]) {
            case "load":
                $version = $args[2] ?? null;
                $name = $args[1];
                SkinDatabase::getInstance()->loadSkin($name, function(bool $success) use ($sender): void{
                    if(!$sender->isConnected()) return;
                    if($success) {
                        $sender->getLevelNonNull()->addSound(new BlazeShootSound($sender->asVector3()), [$sender]);
                        $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GREEN."Skin aus Skindatenbank erfolgreich geladen ;)");
                    }else {
                        $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Skin konnte nicht geladen werden :/");
                    }
                }, $version, $sender);
                break;
            case "save":
                $version = $args[2] ?? null;
                if($version === null) {
                    $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."/skindb save <Name> <version>");
                    return;
                }
                $name = $args[1];
                SkinDatabase::getInstance()->saveSkin($sender->getSkin(), $name, $version);
                $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GREEN."Skin wurde in die Skindatenbank geladen.");
                $sender->playSound("note.bass", 1.0, 2.0, [$sender]);
                break;
        }
    }
}