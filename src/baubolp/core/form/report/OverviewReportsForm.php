<?php


namespace baubolp\core\form\report;


use baubolp\core\provider\ReportProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class OverviewReportsForm extends MenuForm
{

    public function __construct()
    {
        $reports = [];
        $options = [];

        foreach (ReportProvider::getReports() as $badPlayer => $data) {
            $options[] = new MenuOption(TextFormat::YELLOW.$badPlayer);
            $reports[] = $badPlayer;
        }

        $options[] = new MenuOption(TextFormat::DARK_GREEN."Archiv");
        $reports[] = "Archiv";

        $onSubmit = function (Player $player, int $selectedOption) use ($reports): void {
            $badPlayer = $reports[$selectedOption];
            if($badPlayer != "Archiv") {
                $player->sendForm(new ProcessReportForm($badPlayer));
            }else {
                $player->sendForm(new SearchPlayerInArchivForm());
            }
        };

        if(count($reports) == 0) {
            $text = TextFormat::RED."Es sind aktuell keine Reports offen. Gute Arbeit ;)";
        }else {
            $text = "";
        }
        parent::__construct(Ryzer::PREFIX.'Overview', $text, $options, $onSubmit);
    }
}