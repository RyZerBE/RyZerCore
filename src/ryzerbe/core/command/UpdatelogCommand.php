<?php

namespace ryzerbe\core\command;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\UpdatelogEntry;
use function implode;
use function intval;
use function strval;
use function time;

class UpdatelogCommand extends Command {

    public function __construct(){
        parent::__construct("updatelog", "updatelog website admin", "", ["upweb"]);
    }

    /**
     * @param Player $player
     * @param UpdatelogEntry $entry
     */
    protected static function sendForm(Player $player, UpdatelogEntry $entry){
        $types = [
            "ADD",
            "REMOVE",
            "FIX"
        ];
        $form = new CustomForm(function(Player $player, $data) use ($entry, $types): void{
            if($data === null) {
                $form = new SimpleForm(function(Player $player, $data) use ($entry): void{
                    if($data === null) return;

                    $entry->save();
                    $player->sendMessage(RyZerBE::PREFIX.TextFormat::GREEN."Der Updatelog wurde gepostet!");
                });

                $content = [];
                $content[] = TextFormat::BLUE."Title: ".TextFormat::WHITE.$entry->getTitle();
                $content[] = TextFormat::BLUE."Version: ".TextFormat::WHITE.$entry->getVersion();
                $content[] = TextFormat::BLUE."Image: ".TextFormat::WHITE.$entry->getImage();
                $content[] = TextFormat::BLUE."Timestamps: ".TextFormat::WHITE.$entry->getTimestamp();
                $content[] = TextFormat::BLUE."Changes: ".TextFormat::WHITE.implode("\n".TextFormat::BLUE."=> ".TextFormat::WHITE, $entry->getChanges());
                $form->setContent(implode("\n", $content));
                $form->addButton("Post my changelog");
                $form->sendToPlayer($player);
                return;
            }

            $type = $types[$data["type"]];
            $change = $data["change"];

            $entry->addChange($change);
            UpdatelogCommand::sendForm($player, $entry);
        });

        $form->addInput(TextFormat::GREEN."Change", "", "", "change");
        $form->addDropdown(TextFormat::GREEN."Type (SOON - Wait of xAroxx)", $types, null, "type");
        $form->addLabel(TextFormat::GREEN."Close to finish");
        $form->sendToPlayer($player);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;
        if(!$sender->hasPermission("ryzer.admin")) return;

        $form = new CustomForm(function(Player $player, $data): void{
            if($data === null) return;

            $title = $data["title"];
            $version = $data["version"];
            $image = $data["image"];
            $timestamp = intval($data["timestamp"]);

            $entry = new UpdatelogEntry($title, $version, $image, $timestamp);
            UpdatelogCommand::sendForm($player, $entry);
        });

        $form->addInput(TextFormat::GREEN."Title", "Lorem ipsum 2", "", "title");
        $form->addInput(TextFormat::GREEN."Version", "0.2-alpha", "", "version");
        $form->addInput(TextFormat::GREEN."Image", "", "", "image");
        $form->addInput(TextFormat::GREEN."Timestamp", strval(time()), strval(time()), "timestamp");
        $form->setTitle(TextFormat::GREEN.TextFormat::BOLD."Updatelog");
        $form->addLabel(TextFormat::GREEN."Click to continue");
        $form->sendToPlayer($sender);
    }
}