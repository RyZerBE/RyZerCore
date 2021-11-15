<?php

namespace ryzerbe\core\form\types\report;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\ReportProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use function count;

class ReportsOverviewForm {

    public static function onOpen(Player $player){
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(\mysqli $mysqli): array{
            $reports = ReportProvider::getReportsByState($mysqli);
            foreach(ReportProvider::getReportsByState($mysqli, ReportProvider::PROCESS) as $bad_player => $data) $reports[$bad_player] = $data;

            return $reports;
        }, function(Server $server, array $reports) use ($player): void{
            if(!$player->isConnected()) return;
            $form = new SimpleForm(function(Player $player, $data) use ($reports): void{
                if($data === null) return;

                ReportOptionForm::onOpen($player, $reports[$data]);
            });

            $form->setTitle(TextFormat::BLUE."Reports Panel");
            if(count($reports) === 0) {
                $form->setContent(TextFormat::RED."Gute Arbeit! Aktuell gibt es keinen offenen Report!");
            }else {
                foreach($reports as $bad_player => $data) {
                    $form->addButton(TextFormat::RED.$bad_player."\n".ReportProvider::stateToString((int)$data["state"]), -1, "", $bad_player);
                }
            }
            $form->sendToPlayer($player);
        });
    }
}