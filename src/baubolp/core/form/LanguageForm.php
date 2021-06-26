<?php


namespace baubolp\core\form;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class LanguageForm extends MenuForm
{

    public function __construct(string $player)
    {
        $languages = ['English', 'Deutsch'];
        $options = [];
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($player)) != null) {
            foreach ($languages as $language) {
                if(strtolower($language) == strtolower($obj->getLanguage())) {
                    $options[] = new MenuOption(TextFormat::RED.$language."\n".TextFormat::WHITE."> ".TextFormat::YELLOW."Selected".TextFormat::WHITE." <");
                }else {
                    $options[] = new MenuOption(TextFormat::YELLOW.$language);
                }
            }
        }
        parent::__construct(Ryzer::PREFIX."Language", "", $options, function (Player $player, int $selectedOption) use ($languages) : void{
            $language = $languages[$selectedOption];
            if(($obj = RyzerPlayerProvider::getRyzerPlayer($player->getName())) != null) {
                $obj->setLanguage($language);
                LanguageProvider::setLanguage($player->getName(), $language);
                $player->sendMessage(LanguageProvider::getMessageContainer('selected-language', $player->getName(), ['#language' => $language]));
            }
        }, null);
    }
}