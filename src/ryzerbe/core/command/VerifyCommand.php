<?php

namespace ryzerbe\core\command;

use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\form\types\VerifyForm;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;

class VerifyCommand extends Command {

    public function __construct(){
        parent::__construct("verify", "verify with our discord", "", []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;

        $playerName = $sender->getName();
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName): array{
            $res = $mysqli->query("SELECT * FROM verify WHERE player='$playerName'");
            if($res->num_rows <= 0) {
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $charactersLength = strlen($characters);
                $token = '';
                for ($i = 0; $i < 5; $i++) {
                    $token .= $characters[rand(0, $charactersLength - 1)];
                }

                $mysqli->query("INSERT INTO `verify`(`player`, `token`, `verified`) VALUES ('$playerName', '$token', '')");
                return ["token" => $token, "verified" => false];
            }

            return ["token" => $res->fetch_assoc()["token"] ?? "???", "verified" => $res->fetch_assoc()["verified"] != "false"];
        }, function(Server $server, array $result) use ($playerName): void{
            $player = $server->getPlayerExact($playerName);
            if($player === null) return;

            if($result["verified"]) {
                $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("already-verified", $player));
                return;
            }
            VerifyForm::onOpen($player, $result);
        });
    }
}