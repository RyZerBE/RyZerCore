<?php

namespace ryzerbe\core\command;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\networklevel\NetworkLevelProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\RyZerBE;

class NetworkLevelCommand extends Command {
    public function __construct(){
        parent::__construct("networklevel", "Network Level Command");
        $this->setPermission("ryzer.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player) return;
        $target = Server::getInstance()->getPlayer(($args[0] ?? $sender->getName()));
        $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($target);
        if($rbePlayer === null){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Der Spieler ist nicht online.");
            return;
        }
        if(empty($args[0]) || !$this->testPermission($sender)){
            $form = new SimpleForm(function(Player $player, $data): void{
                $player->sendMessage(TextFormat::RED . "Wir arbeiten gerade daran!");
                $player->playSound("note.bass", 5.0, 1.0, [$player]);
            });
            $networkLevel = $rbePlayer->getNetworkLevel();
            $nextLevel = ($networkLevel->getLevel() + 1);
            $progressPercentage = $rbePlayer->getNetworkLevel()->getProgressPercentage();
            $neededXP = $networkLevel->getProgressToLevelUp($networkLevel->getLevel() + 1) - $networkLevel->getProgress();
            $multiplierPercentage = $networkLevel->getMultiplier() * 100;
            //MAX_PROGRESS - 30
            //CURRENT_PROGRESS - ??
            $maxBar = 90;
            $reachedbar = (($networkLevel->getProgress() * $maxBar) / $networkLevel->getProgressToLevelUp());
            $XPDiagram = str_repeat(TextFormat::GOLD . "|", $reachedbar) . str_repeat(TextFormat::GRAY . "|", $maxBar - $reachedbar);
            $message = [
                LanguageProvider::getMessageContainer("your-level", $sender->getName()) . " " . $networkLevel->getLevelColor() . $networkLevel->getLevel(),
                LanguageProvider::getMessageContainer("today-collected-xp", $sender->getName()) . " " . TextFormat::GOLD . $networkLevel->getProgressToday() . " XP",
                "",
                LanguageProvider::getMessageContainer("your-progress", $sender->getName()) . TextFormat::GREEN . " " . $progressPercentage . TextFormat::RESET . "%%",
                "",
                $XPDiagram,
                "",
                LanguageProvider::getMessageContainer("next-level", $sender->getName(), [
                    "#nextlevel" => $networkLevel->getLevelColor($nextLevel) . $nextLevel,
                    "#xp" => $neededXP,
                ]),
            ];
            $form->setContent(implode("\n", $message));
            $form->setTitle(TextFormat::LIGHT_PURPLE . "Network Level");
            foreach(NetworkLevelProvider::getRewards() as $reward){
                if($reward->getLevel() <= $networkLevel->getLevel()){
                    $form->addButton($rbePlayer->getNetworkLevel()->getLevelColor($reward->getLevel()) . $reward->getName() . "\n" . TextFormat::GREEN . "✔ RECEIVED ", 0, "textures/ui/confirm.png");
                }
                else{
                    $form->addButton($rbePlayer->getNetworkLevel()->getLevelColor($reward->getLevel()) . $reward->getName() . "\n" . TextFormat::GRAY . "Level §8• " . $rbePlayer->getNetworkLevel()->getLevelColor($reward->getLevel()) . $reward->getLevel());
                }
            }
            $form->sendToPlayer($sender);
            return;
        }
        $form = new CustomForm(function(Player $player, $data) use ($rbePlayer, $target): void{
            if($data === null) return;
            $networkLevel = $rbePlayer->getNetworkLevel();
            $networkLevel->setLevel((int)$data["level"]);
            $networkLevel->setProgress((int)$data["progress"]);
            $networkLevel->addProgress((int)$data["add_progress"]);
            $player->sendMessage(RyZerBE::PREFIX . "Network Level von " . $target->getName() . " wurde erfolgreich aktualisiert.");
        });
        $form->setTitle("§lNetwork Level");
        $form->addInput("Level", "", $rbePlayer->getNetworkLevel()->getLevel(), "level");
        $form->addInput("Progress", "", $rbePlayer->getNetworkLevel()->getProgress(), "progress");
        $form->addInput("Add Progress", "", "0", "add_progress");
        $form->sendToPlayer($sender);
    }
}