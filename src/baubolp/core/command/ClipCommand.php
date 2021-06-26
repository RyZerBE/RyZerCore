<?php


namespace baubolp\core\command;


use baubolp\core\Ryzer;
use matze\replaysystem\recorder\replay\Replay;
use matze\replaysystem\recorder\replay\ReplayManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClipCommand extends Command
{

    public function __construct()
    {
        parent::__construct("clip", "Save the last 30 secs", "", []);
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        $replay = ReplayManager::getInstance()->getReplayByLevel($sender->getLevel());

        if(is_null($replay)) {
            $sender->sendMessage(Clutches::Prefix.TextFormat::RED."NO_REPLAY_RUNNING");
            return;
        }

        $replay->stopRecording(true,$replay->getTick() - (20 * 30), $replay->getTick());

        $newReplay = new Replay($sender->getLevel());
        $newReplay->startRecording();
        $newReplay->setSpawn($sender->asVector3());
        $sender->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Replay saved. §7ID: §6".$replay->getId());
    }
}