<?php

namespace ryzerbe\core\form\types\report;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\ReportProvider;
use function implode;

class ReadArchiveDataForm {

    /**
     * @param Player $player
     * @param array $data
     */
    public static function onOpen(Player $player, array $report){
        $form = new SimpleForm(function(Player $player, $data): void{});
        $content = [];
        $content[] = TextFormat::GOLD."Spieler: ".TextFormat::WHITE.$report["bad_player"];
        $content[] = TextFormat::GOLD."Grund: ".TextFormat::WHITE.$report["reason"];
        $content[] = TextFormat::GOLD."Gemeldet von: ".TextFormat::WHITE.$report["created_by"];
        $content[] = TextFormat::GOLD."Nick: ".$report["nick"];
        $content[] = TextFormat::GOLD."Notiz: ".TextFormat::WHITE.$report["notice"];
        $content[] = TextFormat::GOLD."Ergebnis: ".TextFormat::WHITE.ReportProvider::stateToString($report["result"]);
        $content[] = TextFormat::GOLD."Bearbeitet von: ".TextFormat::WHITE.$report["staff"];
        $form->setContent(implode("\n", $content));
        $form->sendToPlayer($player);
    }
}