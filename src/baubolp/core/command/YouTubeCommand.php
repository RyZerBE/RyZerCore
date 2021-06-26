<?php


namespace baubolp\core\command;


use baubolp\core\form\YouTuberForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class YouTubeCommand extends Command
{

    public function __construct()
    {
        parent::__construct("youtube", "see our youtuber conditions", "", ['yt']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) return;

        $sender->sendForm(new YouTuberForm($sender->getName()));
    }
}