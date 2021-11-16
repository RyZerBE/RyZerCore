<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\rank\RankMainForm;
use ryzerbe\core\player\PMMPPlayer;
use function implode;

class RankCommand extends Command {
    public function __construct(){
        parent::__construct("rank", "rank admin command", "");
        $this->setPermission("ryzer.rank");
        $this->setPermissionMessage(TextFormat::RED . "No Permission!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof PMMPPlayer) return;
        $rbePlayer = $sender->getRyZerPlayer();
        if(!$this->testPermission($sender) || isset($args[0])){
            $sender->sendMessage(TextFormat::GRAY."Rank: ".$rbePlayer->getRank()->getColor().$rbePlayer->getRank()->getRankName());
            $sender->sendMessage(TextFormat::GRAY."Power: ".TextFormat::GOLD.$rbePlayer->getRank()->getJoinPower());
            $sender->sendMessage(TextFormat::GRAY."Permissions: ".TextFormat::GOLD.implode(", ", $rbePlayer->getRank()->getPermissions()));
            $sender->sendMessage(TextFormat::GRAY."Until: ".TextFormat::GOLD."Never");
            return;
        }
        RankMainForm::onOpen($sender);
    }
}