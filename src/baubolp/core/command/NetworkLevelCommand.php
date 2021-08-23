<?php

namespace baubolp\core\command;

use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function implode;
use function str_repeat;

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
        if(!$sender instanceof Player) return;

        $target = Server::getInstance()->getPlayer(($args[0] ?? $sender->getName()));
        $rbePlayer = RyzerPlayerProvider::getRyzerPlayer($target->getName());
        if($rbePlayer === null){
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Spieler ist nicht online.");
            return;
        }

        if(empty($args[0])|| !$this->testPermission($sender)) {
            $form = new SimpleForm(function(Player $player, $data): void{
                $player->sendMessage(TextFormat::RED."Wir arbeiten gerade daran!");
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
            $XPDiagram = str_repeat(TextFormat::GOLD."|", $reachedbar).str_repeat(TextFormat::GRAY."|", $maxBar - $reachedbar);
            $message = [
                LanguageProvider::getMessageContainer("your-level", $sender->getName())." ".$networkLevel->getLevelColor().$networkLevel->getLevel(),
                LanguageProvider::getMessageContainer("today-collected-xp", $sender->getName())." ".TextFormat::GOLD.$networkLevel->getProgressToday()." XP",
                "",
                LanguageProvider::getMessageContainer("your-progress", $sender->getName()).TextFormat::GREEN." ".$progressPercentage.TextFormat::RESET."%%",
                "",
                $XPDiagram,
                "",
                LanguageProvider::getMessageContainer("next-level", $sender->getName(), ["#nextlevel" => $networkLevel->getLevelColor($nextLevel).$nextLevel, "#xp" => $neededXP])
            ]; //todo: if you give xp, sent the player information about the multiplier (e.g You get 45 XP. Your Multiplier is under 100% so you got only 30% (=XP-PERCENT) of the won xp)

            $form->setContent(implode("\n", $message));
            $form->setTitle(TextFormat::LIGHT_PURPLE."Network Level");
            $form->addButton(TextFormat::GOLD."Belohnung 1");
            $form->addButton(TextFormat::RED."Belohnung 2");
            $form->addButton(TextFormat::GOLD."Belohnung 3");
            $form->addButton(TextFormat::GOLD."Belohnung 4");
            $form->addButton(TextFormat::GOLD."Belohnung 5");
            $form->addButton(TextFormat::GOLD.".....");
            $form->sendToPlayer($sender);
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