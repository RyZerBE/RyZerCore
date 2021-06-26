<?php


namespace baubolp\core\form;

use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ApplyInformationForm extends MenuForm
{

    public function __construct(string $playerName)
    {
        $options = [];
        $options[] = new MenuOption(TextFormat::DARK_AQUA."Content");
        $options[] = new MenuOption(TextFormat::DARK_GREEN."Builder");
        $options[] = new MenuOption(TextFormat::RED."Staff");
        $options[] = new MenuOption(TextFormat::AQUA."Developer");
        $text = LanguageProvider::getMessageContainer('apply-text-question', $playerName);
        parent::__construct(Ryzer::PREFIX.TextFormat::GREEN."Apply", $text, $options, function (Player $player, int $selectedOption): void{
            $player->sendForm(new ApplyDescriptionForm($selectedOption));
        });
    }
}