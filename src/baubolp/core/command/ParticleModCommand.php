<?php


namespace baubolp\core\command;


use baubolp\core\form\ParticleModForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ParticleModCommand extends Command
{

    public function __construct()
    {
        parent::__construct("particlemod", "de/activate always particles", "", ['pm', 'particle', 'particles']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        $sender->sendForm(new ParticleModForm($sender->getName()));
    }
}