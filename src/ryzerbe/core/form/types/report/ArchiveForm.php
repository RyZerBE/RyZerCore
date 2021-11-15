<?php

namespace ryzerbe\core\form\types\report;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\ReportProvider;
use ryzerbe\core\util\async\AsyncExecutor;

class ArchiveForm {

    public static function onOpen(Player $player, string $playerName){
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(\mysqli $mysqli) use ($playerName): array{
            return ReportProvider::getReportArchiveOfPlayer($mysqli, $playerName);
        }, function(Server $server, array $archive) use ($player): void{
            if(!$player->isConnected()) return;
            $form = new SimpleForm(function(Player $player, $data) use ($archive): void{
                if($data === null) return;

                ReadArchiveDataForm::onOpen($player, $archive[$data]);
            });

            $form->setTitle(TextFormat::BLUE."Reports Panel");
            foreach($archive as $id => $data) {
                $form->addButton(TextFormat::GREEN.$data["created_date"], -1, "", $id);
            }
            $form->sendToPlayer($player);
        });
    }
}