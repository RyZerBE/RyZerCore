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
use function explode;
use function in_array;
use function is_nan;
use function is_numeric;
use function is_string;
use function str_contains;
use function time;
use function var_dump;

class JoinMeCommand extends Command {

    private array $forbiddenGroups = [
        "Lobby",
        "EloCWBW",
        "FunCWBW",
        "TrainingLobby"
    ];

    public function __construct(){
        parent::__construct("joinme", "join a joinme #stupid", "", ["jm"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(isset($args[0]) && $sender->hasPermission("ryzer.joinme.tokens.add")) {
            switch($args[0]) {
                case "add":
                    if(empty($args[1]) || empty($args[2])) {
                        $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."/joinme add <Player:string> <Token:int>");
                        return;
                    }

                    $playerName = $args[1];
                    $count = $args[2];
                    if(!is_numeric($count) || !is_string($playerName)) return;

                    AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $count): void{
                        $mysqli->query("INSERT INTO `joinme_tokens`(`player`, `tokens`) VALUES ('$playerName', '$count') ON DUPLICATE KEY UPDATE tokens=tokens+'$count'");
                    }, function(Server $server, $result) use ($sender, $playerName, $count): void{
                        $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Du hast dem Spieler ".TextFormat::GOLD.$playerName.TextFormat::AQUA." $count JoinME Tokens ".TextFormat::GRAY." hinzugefügt");
                    });
                    break;
            }
            return;
        }
        if(!$sender instanceof PMMPPlayer) return;

        $senderName = $sender->getName();
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($senderName): array{
            $res = $mysqli->query("SELECT * FROM joinme");
            $joinMe = [];
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()){
                    $diff = (new DateTime())->diff(new DateTime($data["time"]));
                    if($diff->i >= 1){
                        $mysqli->query("DELETE FROM joinme WHERE player='" . $data["player"] . "'");
                        continue;
                    }
                    $joinMe[$data["player"]] = $data["server"];
                }
            }

            $res = $mysqli->query("SELECT * FROM joinme_tokens WHERE player='$senderName'");
            if($res->num_rows > 0) {
                $joinMe["tokens"] = $res->fetch_assoc()["tokens"] ?? 0;
            }else {
                $joinMe["tokens"] = 0;
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
                    if(str_contains(CloudProvider::getServer(), "2x1")) {
                        $ryzerPlayer->sendTranslate("joinme-too-small-round");
                        return;
                    }
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
                    $hasPermission = $player->hasPermission("ryzer.joinme");
                    AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name, $serverName, $hasPermission){
                        $mysqli->query("INSERT INTO `joinme`(`player`, `server`) VALUES ('$name', '$serverName')");
                        if(!$hasPermission) $mysqli->query("UPDATE `joinme_tokens` SET tokens=tokens-1 WHERE player='$name'");
                    }, function(Server $server, $result) use ($name, $serverName){
                        if(($player = $server->getPlayerExact($name)) !== null){
                            $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer('joinme-created', $player->getName(), ["#server" => $serverName]));
                        }
                    });
                    return;
                }
                $ryzerPlayer->connectServer($data);
            });

            $form->setTitle(TextFormat::AQUA.TextFormat::BOLD."JoinMe");
            if(($player->hasPermission("ryzer.joinme") || $joinMe["tokens"] > 0)){
                if(!in_array(explode("-", CloudProvider::getServer())[0], $this->forbiddenGroups)) $form->addButton(TextFormat::GREEN."Create JoinMe", 1, "https://media.discordapp.net/attachments/602115215307309066/907983757846380594/3212872.png?width=410&height=410", "create");
            }
            else $form->setContent(LanguageProvider::getMessageContainer("joinme-token-info", $player->getName()));

            unset($joinMe["tokens"]);
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