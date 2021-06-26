<?php


namespace baubolp\core\form\report;


use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SelectArchiveFolderForm extends MenuForm
{

    public function __construct(array $options, array $reportData, array $timeCache)
    {
        $onSubmit = function (Player $player, int $selectedOption) use ($reportData, $timeCache): void{
             $archivData = $reportData[$timeCache[$selectedOption]];
             $player->sendForm(new ArchiveInformationForm($archivData));
        };
        parent::__construct(Ryzer::PREFIX.TextFormat::DARK_GREEN."Archiv", "", $options, $onSubmit);
    }
}