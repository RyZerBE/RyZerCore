<?php

namespace ryzerbe\core\form\types\clan;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CWHistoryDisplayForm {
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data): void{
        });
        $data = $extraData["data"] ?? [];
        if($data === []) return;
        $form->setTitle(TextFormat::GOLD . "ClanWar History");
        $display = TextFormat::GOLD . TextFormat::BOLD . "Winner: " . TextFormat::RESET . TextFormat::AQUA . $data["clan_1"] . "\n";
        $display .= TextFormat::GOLD . TextFormat::BOLD . "Loser: " . TextFormat::RESET . TextFormat::AQUA . $data["clan_2"] . "\n";
        $display .= "" . "\n";
        $display .= TextFormat::GOLD . TextFormat::BOLD . "Playtime: " . TextFormat::RESET . TextFormat::AQUA . $data["playtime"] . "\n";
        $display .= TextFormat::GOLD . TextFormat::BOLD . "Map: " . TextFormat::RESET . TextFormat::AQUA . $data["map"] . "\n";
        $display .= TextFormat::GOLD . TextFormat::BOLD . "Elo: " . TextFormat::RESET . TextFormat::AQUA . $data["elo"] . "\n";
        $display .= "" . "\n";
        $display .= TextFormat::GOLD . TextFormat::BOLD . "Bed of Winner: " . TextFormat::RESET . (($data["bed_clan_1"] === "ALIVE") ? TextFormat::GREEN . TextFormat::BOLD . "✔" : TextFormat::RED . "DESTROYED BY " . TextFormat::YELLOW . $data["bed_clan_1"]) . "\n";
        $display .= TextFormat::GOLD . TextFormat::BOLD . "Bed of Loser: " . TextFormat::RESET . (($data["bed_clan_2"] === "ALIVE") ? TextFormat::GREEN . TextFormat::BOLD . "✔" : TextFormat::RED . "DESTROYED BY " . TextFormat::YELLOW . $data["bed_clan_2"]) . "\n";
        $form->setContent($display);
        $form->sendToPlayer($player);
    }
}