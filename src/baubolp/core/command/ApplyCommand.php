<?php


namespace baubolp\core\command;


use baubolp\core\form\ApplyInformationForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ApplyCommand extends Command
{

    public function __construct()
    {
        parent::__construct("apply", "", "", ['bewerben']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        $sender->sendForm(new ApplyInformationForm($sender->getName()));
    }
}