<?php

namespace ryzerbe\core\command;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use ryzerbe\core\provider\ChatEmojiProvider;
use function implode;

class EmojiListCommand extends Command {

    public function __construct(){
        parent::__construct("emojilist", "See list of emojis", "", ["elist"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;

        $form = new SimpleForm(function(Player $player, $data): void{});
        $content = [];

        foreach(ChatEmojiProvider::EMOJIS as $emojiName => $emoji) {
            $content[] = ":$emojiName: ".$emoji;
        }

        $form->setContent(implode("\n", $content));
        $form->sendToPlayer($sender);
    }
}