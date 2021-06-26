<?php


namespace baubolp\core\form\report;


use baubolp\core\provider\ModerationProvider;
use baubolp\core\provider\ReportProvider;
use baubolp\core\Ryzer;
use pocketmine\form\CustomForm;
use pocketmine\form\CustomFormResponse;
use pocketmine\form\element\Input;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SearchPlayerInArchivForm extends CustomForm
{

    public function __construct()
    {
        $elements = [new Input('Name des Spielers', '', 'Steve', '')];
        $onSubmit = function (Player $player, CustomFormResponse $response): void {
            $options = [];
            $element = $this->getElement(0);
                if($element instanceof Input) {
                    $badPlayer = $response->getString($element->getName());
                    $archiveData = [];
                    $timeCache = [];
                    foreach (ReportProvider::getArchive() as $archive) {
                        if($archive['badPlayer'] == $badPlayer) {
                            $timeCache[] = $archive['time'];
                            $archiveData[$archive['time']] = $archive;
                          $options[] = new MenuOption(TextFormat::YELLOW.ModerationProvider::formatGermanDate($archive['time']));
                        }
                    }
                    $player->sendForm(new SelectArchiveFolderForm($options, $archiveData, $timeCache));
                }
        };
        parent::__construct(Ryzer::PREFIX.TextFormat::DARK_GREEN."Archiv", $elements, $onSubmit);
    }
}