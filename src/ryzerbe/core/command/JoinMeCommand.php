<?php

namespace ryzerbe\core\command;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMessagePacket;
use BauboLP\Cloud\Provider\CloudProvider;
use DateTime;
use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\event\player\game\JoinMeCreateEvent;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use function count;
use function time;

class JoinMeCommand extends Command {
    public function __construct(){
        parent::__construct("joinme", "join a joinme #stupid", "", ["jm"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof PMMPPlayer) return;
        $time = time();
        $senderName = $sender->getName();
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($time): array{
            $res = $mysqli->query("SELECT * FROM joinme");
            $joinMe = [];
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()){
                    if((new DateTime($data["time"]))->diff(new DateTime())->i >= 1){
                        $mysqli->query("DELETE FROM joinme WHERE player='" . $data["player"] . "'");
                        continue;
                    }
                    $joinMe[$data["player"]] = $data["server"];
                }
            }
            return $joinMe;
        }, function(Server $server, array $joinMe) use ($senderName): void{
            $player = $server->getPlayerExact($senderName);
            if($player === null) return;
            $form = new SimpleForm(function(Player $player, $data): void{
                if($data === null) return;
                $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
                if($ryzerPlayer === null) return;
                if($data === "create"){
                    $ev = new JoinMeCreateEvent($ryzerPlayer);
                    $ev->call();
                    if($ev->isCancelled()) return;
                    $message = "\n\n" . "&bJoinMe &8| &a" . $player->getName() . " &r&7created a JoinME on &6" . CloudProvider::getServer() . "&7.\n" . "&bJoinMe &8| &7Join him with &6/joinme&7.\n\n";
                    $pk = new PlayerMessagePacket();
                    $pk->addData("players", "ALL");
                    $pk->addData("message", $message);
                    CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
                    $name = $player->getName();
                    $serverName = CloudProvider::getServer();
                    AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name, $serverName){
                        $mysqli->query("INSERT INTO `joinme`(`player`, `server`) VALUES ('$name', '$serverName')");
                    }, function(Server $server, $result) use ($name, $serverName){
                        if(($player = $server->getPlayerExact($name)) !== null){
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer('joinme-created', $player->getName(), ["#server" => $serverName]));
                        }
                    });
                    return;
                }
                $ryzerPlayer->connectServer($data);
            });
            if($player->hasPermission("ryzer.joinme")) $form->addButton(TextFormat::GREEN . "Create JoinMe", 1, "https://media.discordapp.net/attachments/602115215307309066/907983757846380594/3212872.png?width=410&height=410", "create");
            if(count($joinMe) === 0){
                $form->addButton(LanguageProvider::getMessageContainer("no-joinme-exist", $player->getName()), 1, "https://media.discordapp.net/attachments/602115215307309066/907985420690808842/480px-Red_x.png?width=384&height=384");
            }
            else{
                foreach($joinMe as $playerName => $server){
                    $form->addButton($playerName . "\n" . TextFormat::GOLD . $server, 1, "https://media.discordapp.net/attachments/602115215307309066/907982544081924106/jump.png?width=410&height=410", $server);
                }
            }
            $form->sendToPlayer($player);
        });
    }
}