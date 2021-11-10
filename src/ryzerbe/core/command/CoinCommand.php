<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\CoinProvider;
use ryzerbe\core\RyZerBE;
use function is_numeric;
use function is_string;

class CoinCommand extends Command {

    public function __construct(){
        parent::__construct("coins", "View your coins", "", []);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;

        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($sender);
        if($ryzerPlayer === null) return;

        if(empty($args[0]) || !$sender->hasPermission("ryzer.coins.admin")) {
            $sender->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('get-coins', $sender->getName(), ['#coins' => $ryzerPlayer->getCoins()]));
            return;
        }

        if(empty($args[1]) || empty($args[2])) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Syntax error: /coins <add|set|remove> <Spieler:string> <Coins:int>");
            return;
        }

        $playerName = $args[1];
        $coins = $args[2];
        if(!is_string($playerName) || !is_numeric($coins) || $coins < 0) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Du bist einfach zu dumm um Coins zu vergeben..");
            return;
        }
        switch($args[0]) {
            case "add":
                CoinProvider::addCoins($playerName, $coins);
                $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Dem Coin-Stand des Spielers ".TextFormat::AQUA.$playerName.TextFormat::GRAY." wurden ".TextFormat::YELLOW.$coins.TextFormat::GRAY." Coins hinzugefügt.");
                break;
            case "set":
                CoinProvider::setCoins($playerName, $coins);
                $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Die Coins des Spielers ".TextFormat::AQUA.$playerName.TextFormat::GRAY." wurden auf ".TextFormat::YELLOW.$coins.TextFormat::GRAY." Coins gesetzt.");
                break;
            case "remove":
                CoinProvider::removeCoins($playerName, $coins);
                $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Dem Coin-Stand des Spielers ".TextFormat::AQUA.$playerName.TextFormat::GRAY." wurden ".TextFormat::YELLOW.$coins.TextFormat::GRAY." Coins entfernt.");
                break;
        }
    }
}