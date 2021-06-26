<?php


namespace baubolp\core\command;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\CoinProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CoinCommand extends Command
{

    public function __construct()
    {
        parent::__construct('coins', "See your coins", "", ['coin']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        if(($obj = RyzerPlayerProvider::getRyzerPlayer($sender->getName())) != null) {
            if(empty($args[0]) || empty($args[1])) {
                $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('get-coins', $sender->getName(), ['#coins' => $obj->getCoins()]));
                return;
            }

            if(!$sender->hasPermission("core.coins.edit")) {
                $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
                return;
            }

            if(empty($args[2])) {
                $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/coins <add|set|remove> <Player> <Coins>");
                return;
            }

            $coins = $args[2];
            $playerName = $args[1];

            if(!is_string($playerName) || !is_numeric($coins)) {
                $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Cointwert muss eine Zahl und der Spielername ein String sein!");
                return;
            }

            switch ($args[0]) {
                case "set":
                    CoinProvider::setCoins($playerName, $coins);
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Die Coins des Spielers ".TextFormat::AQUA.$playerName.TextFormat::GRAY." wurden auf ".TextFormat::YELLOW.$coins.TextFormat::GRAY." gesetzt.");
                    break;
                case "add":
                    CoinProvider::addCoins($playerName, $coins);
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Dem Coin-Stand des Spielers ".TextFormat::AQUA.$playerName.TextFormat::GRAY." wurden ".TextFormat::YELLOW.$coins.TextFormat::GRAY." Coins hinzugefügt.");
                    break;
                case "remove":
                    CoinProvider::removeCoins($playerName, $coins);
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Dem Coin-Stand des Spielers ".TextFormat::AQUA.$playerName.TextFormat::GRAY." wurden ".TextFormat::YELLOW.$coins.TextFormat::GRAY." Coins entfernt.");
                    break;
            }
        }
    }
}