<?php

namespace ryzerbe\core\command;

use baubolp\ryzerbe\lobbycore\form\event\EventForm;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use function date;
use function strtotime;

class CoinBoostCommand extends Command {

    public function __construct(){
        parent::__construct("coinboost", "coinboost command admin", "", []);
        $this->setPermission("ryzer.coinboost.admin");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;

            $playerName = $data["player"];
            $percent = $data["percent"];
            $time = strtotime($data["time"]);
            $isForAll = (int)$data["all"];
            if($time === false) {
                $player->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Bitte überprüfe deine Zeitangaben!");
                return;
            }

            $date = date("Y-m-d H:i:s", $time);

            $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($playerName);
            if($rbePlayer !== null) {
                $rbePlayer->giveCoinboost($percent, new \DateTime($date), false, true);
                $player->sendMessage(RyZerBE::PREFIX.TextFormat::GOLD.$playerName.TextFormat::GRAY." hat seinen §eprozentualen Coinboost §7von §c§l".$percent."%§r§a erhalten.");
                return;
            }
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $isForAll, $percent, $date){
                $mysqli->query("INSERT INTO `coinboosts`(`player`, `time`, `percent`, `for_all`) VALUES ('$playerName', '$date', '$percent', '$isForAll') ON DUPLICATE KEY UPDATE percent='$percent',time='$date',for_all='$isForAll'");
            }, function(Server $server, $result) use ($player, $playerName, $percent): void{
                if(!$player->isConnected()) return;
                $player->sendMessage(RyZerBE::PREFIX.TextFormat::GOLD.$playerName.TextFormat::GRAY." hat seinen §eprozentualen Coinboost §7von §c§l".$percent."%§r§a erhalten.");
            });
        });

        $form->addInput(TextFormat::GREEN."Name des Spielers", "Chillihero", "", "player");
        $form->addSlider(TextFormat::GREEN."Um wie viel Prozent sollen die Coins geboostet werden?", 5, 100,-1, -1, "percent");
        $form->addInput(TextFormat::GREEN."Bis wann?", "bspw. 12.04.2022 15:00", "", "time");
        $form->addToggle(TextFormat::GREEN."Alle Spieler der Runde", false, "all");
        $form->sendToPlayer($sender);
    }
}