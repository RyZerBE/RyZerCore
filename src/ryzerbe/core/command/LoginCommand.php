<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\RyZerBE;

class LoginCommand extends Command {
    public function __construct(){
        parent::__construct("login", "login to our team systems", "", []);
        $this->setPermission("ryzer.login");
        $this->setPermissionMessage(TextFormat::RED . "No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof PMMPPlayer) return;
        if(!$this->testPermission($sender)) return;
        if(StaffProvider::loggedIn($sender)){
            StaffProvider::logout($sender);
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Du hast dich aus den Teamsystemen " . TextFormat::RED . " ausgeloggt.");
        }
        else{
            StaffProvider::login($sender);
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Du hast dich aus den Teamsystemen " . TextFormat::GREEN . " eingeloggt.");
        }
    }
}