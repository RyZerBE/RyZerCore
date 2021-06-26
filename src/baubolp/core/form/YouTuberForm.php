<?php


namespace baubolp\core\form;


use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class YouTuberForm extends MenuForm
{

    public function __construct(string $playerName)
    {
        $options = [];
        $text = LanguageProvider::getMessageContainer('youtuber-conditions', $playerName, ['#newLine' => "\n"]);
        parent::__construct(Ryzer::PREFIX.TextFormat::DARK_PURPLE."YouTuber", $text, $options, function (Player $player, int $selectedOption): void{});
    }
}