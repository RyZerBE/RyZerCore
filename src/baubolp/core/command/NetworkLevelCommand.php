<?php

namespace baubolp\core\command;

use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class NetworkLevelCommand extends Command {

    /**
     * NetworkLevelCommand constructor.
     */
    public function __construct(){
        parent::__construct("networklevel", "Network Level Command");
        $this->setPermission("command.networklevel.use");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player || !$this->testPermission($sender)) return;
        $target = Server::getInstance()->getPlayer(($args[0] ?? "N/A"));
        if($target === null){
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Spieler ist nicht online.");
            return;
        }
        $rbePlayer = RyzerPlayerProvider::getRyzerPlayer($target->getName());
        if($rbePlayer === null){
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Spieler ist nicht online.");
            return;
        }

        $form = new CustomForm(function(Player $player, $data) use ($rbePlayer, $target): void {
            if($data === null) return;

            $networkLevel = $rbePlayer->getNetworkLevel();
            $networkLevel->setLevel($data["level"]);
            $networkLevel->setProgress($data["progress"]);
            $networkLevel->addProgress($data["add_progress"]);

            $player->sendMessage(Ryzer::PREFIX."Network Level von " . $target->getName() . " wurde erfolgreich aktualisiert.");
        });
        $form->setTitle("§lNetwork Level");
        $form->addInput("Level", "", $rbePlayer->getNetworkLevel()->getLevel(), "level");
        $form->addInput("Progress", "", $rbePlayer->getNetworkLevel()->getProgress(), "progress");
        $form->addInput("Add Progress", "", "0", "add_progress");
        $form->sendToPlayer($sender);
    }
}