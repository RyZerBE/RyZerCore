<?php


namespace baubolp\core\command;


use baubolp\core\provider\AsyncExecutor;
use baubolp\core\Ryzer;
use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class WebInterfaceCommand extends Command
{

    public function __construct()
    {
        parent::__construct("webinterface", "create your webinterface account", "", ["adminpanel"]);
        $this->setPermission("core.webinterface");
    }

    public static function generatePassword(): string
    {
        $id = "";
        $string = "ABCDEFGHIJKLMNOPQRSTUVQXYZ";
        $string .= "abcdefghijklmnopqrstuvwxyz";
        $string .= "123456789";
        $string .= "&!?=)(";
        $string .= "ABCDEFGHIJKLMNOPQRSTUVQXYZ";
        for ($i = 0; 12 > $i; $i++)
            $id .= $string[rand(0, strlen($string) - 1)];

        return $id;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        $playerName = $sender->getName();
        AsyncExecutor::submitMySQLAsyncTask("Webinterface", function (mysqli $mysqli) use($playerName) {
            $res = $mysqli->query("SELECT * FROM login WHERE username='$playerName'");
            if($res->num_rows > 0)
                return true;

            $password = WebInterfaceCommand::generatePassword();
            $passwordHash = hash('sha512', $password);
            $mysqli->query("INSERT INTO login(`username`, `password`) VALUES ('$playerName', '$passwordHash')");
            return $password;
        }, function (Server $server, $result) use ($playerName){
            $player = $server->getPlayerExact($playerName);
            if($player === null) return;

            if(is_bool($result)) {
                $player->sendMessage(Ryzer::PREFIX . TextFormat::RED . "Du hast bereits einen Account im Webinterface!");
                $player->playSound("note.bass", 5.0, 1.0, [$player]);
            }else {
                $player->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Dein Account wurde erstellt.");
                $player->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Benutzername: ".TextFormat::GREEN.$playerName);
                $player->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Passwort: ".TextFormat::GOLD.$result);
                $player->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Du kannst unser Webinterface unter ".TextFormat::GREEN."cp.ryzer.be".TextFormat::GRAY." erreichen.");
                $player->playSound("random.levelup", 5.0, 1.0, [$player]);
            }
        });
    }
}