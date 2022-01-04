<?php

namespace ryzerbe\core\command;

use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use ryzerbe\core\form\types\clan\ClanMainForm;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\async\AsyncExecutor;
use function strtotime;

class ClanUiCommand extends Command {
    public function __construct(){
        parent::__construct("clanui", "Create and manage your clan", "", ["cui"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof PMMPPlayer) return;
        $playerName = $sender->getName();
        AsyncExecutor::submitMySQLAsyncTask("BetterClans", function(mysqli $mysqli) use ($playerName){
            $loadedData = [];
            $res = $mysqli->query("SELECT clan_name,role FROM `ClanUsers` WHERE playername='$playerName'");
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()){
                    $loadedData["clanName"] = $data["clan_name"];
                    $loadedData["role"] = $data["role"];
                }
            }
            $clanName = (string)$loadedData["clanName"];
            if($clanName === ""){
                unset($loadedData["clanName"]);
                $res = $mysqli->query("SELECT * FROM `ClanRequests` WHERE playername='$playerName'");
                if($res->num_rows > 0){
                    while($data = $res->fetch_assoc()){
                        $loadedData["requests"][] = $data["clan_name"];
                    }
                }
                else{
                    $loadedData["requests"] = [];
                }
                return $loadedData; //NO CLAN
            }
            $res = $mysqli->query("SELECT * FROM `ClanRoles`");
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()){
                    $loadedData["roles"][$data["role_name"]] = $data["priority"];
                }
            }
            $res = $mysqli->query("SELECT * FROM Clans WHERE clan_name='$clanName'");
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()){
                    $loadedData["clan_tag"] = $data["clan_tag"];
                    $loadedData["clan_owner"] = $data["clan_owner"];
                    $loadedData["elo"] = $data["elo"];
                    $loadedData["color"] = $data["color"];
                    $loadedData["created"] = $data["created"];
                    $loadedData["status"] = $data["status"];
                    $loadedData["message"] = $data["message"];
                }
            }
            $playerList = [];
            $res = $mysqli->query("SELECT * FROM `ClanUsers` WHERE clan_name='$clanName'");
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()){
                    $playerList[] = $data["playername"];
                }
            }
            $loadedData["players"] = $playerList;
            $res = $mysqli->query("SELECT * FROM CWHistory WHERE clan_1='$clanName' OR clan_2='$clanName'");
            $loadedData["loseMatches"] = 0;
            $loadedData["wonMatches"] = 0;
            if($res->num_rows > 0) {
                while($data = $res->fetch_assoc()){
                    if($loadedData["clanName"] == $data["clan_1"]) $loadedData["wonMatches"]++;
                    else if($loadedData["clanName"] == $data["clan_2"]) $loadedData["loseMatches"]++;
                }
            }


            return $loadedData;
        }, function(Server $server, array $result) use ($playerName): void{
            if(($player = $server->getPlayer($playerName)) !== null){
                ClanMainForm::open($player, $result);
            }
        });
    }
}