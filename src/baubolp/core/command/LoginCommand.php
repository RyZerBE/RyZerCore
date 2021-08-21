<?php


namespace baubolp\core\command;


use baubolp\core\module\TrollSystem\TrollSystem;
use baubolp\core\provider\StaffProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class LoginCommand extends Command
{

    public function __construct()
    {
        parent::__construct('login', "login into/logout from the team systems", "", ['']);
        $this->setPermission("core.login");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED.'No Permissions!');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        if(isset($args[0])) {
            if($args[0] == "list") {
                $sender->sendMessage(Ryzer::PREFIX . TextFormat::GRAY . "Folgende Teamler sind eingeloggt:");
                foreach (StaffProvider::getLoggedStaff() as $staff) {
                    $sender->sendMessage(Ryzer::PREFIX . TextFormat::AQUA . "=> " . TextFormat::YELLOW . $staff);
                }
            }else if($args[0] == "trollmode" && $sender->getName() == "BauboLPYT") {
                if(TrollSystem::isEnabled()) return;
                Ryzer::$trollSystem = New TrollSystem();
                Ryzer::getTrollSystem()->enable(Ryzer::getPlugin());
                $sender->sendMessage(TrollSystem::Prefix.TextFormat::GREEN."Troll-System activated! ;)");
            }else {
                $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/login list");
            }
            return;
        }

        if(StaffProvider::isLogin($sender->getName())) {
            StaffProvider::logout($sender->getName());
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Du wurdest von den Systemen ".TextFormat::RED."ausgeloggt".TextFormat::GRAY.".");
            StaffProvider::sendMessageToStaffs(TextFormat::RED.TextFormat::BOLD."Team ".TextFormat::RESET.TextFormat::DARK_GRAY."| ".$sender->getName().TextFormat::GRAY." hat sich ".TextFormat::RED."ausgeloggt");
        }else {
            StaffProvider::login($sender->getName());
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Du wurdest in die Systeme ".TextFormat::GREEN."eingeloggt".TextFormat::GRAY.".");
            StaffProvider::sendMessageToStaffs(TextFormat::RED.TextFormat::BOLD."Team ".TextFormat::RESET.TextFormat::DARK_GRAY."| ".$sender->getName().TextFormat::GRAY." hat sich ".TextFormat::GREEN."eingeloggt");
        }
    }
}