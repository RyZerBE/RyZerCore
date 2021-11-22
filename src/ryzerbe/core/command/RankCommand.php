<?php

namespace ryzerbe\core\command;

use DateTime;
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
            $duration = $rbePlayer->getRank()->getDuration();
            if($duration === 0) $duration = "Never";
            else {
               $diff = (new DateTime())->diff(new DateTime($duration));
               $duration = "";
               if($diff->m > 0) $duration .= $diff->m."M ";
               if($diff->d > 0) $duration .= $diff->d."D ";
               if($diff->i > 0) $duration .= $diff->i."Min ";
               if($diff->d <= 0 && $diff->s > 0) $duration .= $diff->s."Sec ";
            }
            $sender->sendMessage(TextFormat::GRAY."Until: ".TextFormat::GOLD.$duration);
            return;
        }
        RankMainForm::onOpen($sender);
    }
}