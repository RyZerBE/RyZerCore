<?php

namespace ryzerbe\core\command;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;

class YouTubeCommand extends Command {

    public function __construct(){
        parent::__construct("youtube", "View our youtuber conditions", "", ["yt"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;

        $form = new SimpleForm(function(Player $player, $data): void{});
        $form->setTitle(TextFormat::DARK_PURPLE."YouTuber ".TextFormat::AQUA."Conditions");
        $form->setContent(LanguageProvider::getMessageContainer("youtuber-conditions", $sender->getName()));
        $form->addButton(TextFormat::AQUA."discord.ryzer.be", 1, "https://media.discordapp.net/attachments/570553600821166080/907706651832877156/Discord-Emblem.png?width=1248&height=702");
        $form->sendToPlayer($sender);
    }
}