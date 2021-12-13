<?php

namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\punishment\PunishmentMainForm;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\punishment\PunishmentReason;
use function is_numeric;

class BanCommand extends Command {
    public const CANNOT_BANNED = ["BauboLPYT", "Matze998", "zuWxld"];

    public function __construct(){
        parent::__construct("ban", "ban or mute a player", "", []);
        $this->setPermission("ryzer.ban");
        $this->setPermissionMessage(RyZerBE::PREFIX . TextFormat::RED . "No Permissions");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$this->testPermission($sender)) return;
        if(empty($args[0])){
            $i = 0;
            foreach(PunishmentProvider::getPunishmentReasons() as $banReason){
                $days = $banReason->getDays();
                $hours = $banReason->getHours();
                if($days > 0 && $hours > 0){
                    $duration = TextFormat::GOLD . $days . TextFormat::RED . "D, " . TextFormat::GOLD . $hours . TextFormat::RED . "H";
                }
                else{
                    if($days > 0){
                        $duration = TextFormat::GOLD . $days . TextFormat::RED . "D";
                    }
                    else{
                        if($hours > 0){
                            $duration = TextFormat::GOLD . $hours . TextFormat::RED . "H";
                        }
                        else{
                            $duration = TextFormat::DARK_RED . "PERMANENT";
                        }
                    }
                }
                $typeString = ($banReason->getType() === PunishmentReason::BAN) ? TextFormat::YELLOW . "BAN" : TextFormat::YELLOW . "MUTE";
                $sender->sendMessage(RyZerBE::PREFIX . TextFormat::YELLOW . ++$i . TextFormat::DARK_GRAY . " » " . TextFormat::RED . $banReason->getReasonName() . TextFormat::DARK_GRAY . " | " . $duration . TextFormat::DARK_GRAY . " [" . $typeString . TextFormat::DARK_GRAY . "]");
            }
            return;
        }
        $playerName = $args[0];
        if(in_array($playerName, self::CANNOT_BANNED)) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Dieser Spieler ist geschützt und kann daher nicht gebannt werden!");
            return;
        }
        if(empty($args[1])){
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Syntax error: /ban <PlayerName:string> <BanID:int>");
            return;
        }
        if(!is_numeric($args[1])){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Bitte nutze eine valide BanID!");
            return;
        }
        $punishment = PunishmentProvider::getPunishmentReasonById($args[1]);
        if($punishment === null){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Diese Id existiert nicht!");
            return;
        }
        if($punishment->isPermanent() && !$sender->hasPermission("ryzer.ban.perma")){
            $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Dir fehlt das Recht einen permanenten Ban auszusprechen! Permission: ryzer.ban.perma");
            return;
        }
        PunishmentProvider::punishPlayer($playerName, $sender->getName(), $punishment);
    }
}