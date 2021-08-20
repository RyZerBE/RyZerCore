<?php


namespace baubolp\core\form;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\form\FormIcon;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class LanguageForm extends MenuForm
{

    public function __construct(string $player)
    {
        $languages = ["English", "Deutsch"];
        $options = [];
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($player)) != null) {
            foreach ($languages as $language) {
                if(strtolower($language) == strtolower($obj->getLanguage())) {
                    $options[] = new MenuOption(TextFormat::AQUA.TextFormat::BOLD.$language."\n".TextFormat::WHITE.TextFormat::BOLD.count(array_keys(Ryzer::$translations[$language]))." translations", new FormIcon("https://media.discordapp.net/attachments/779814956270223380/868901452649734304/276speakinghead_100550.png?width=410&height=410"));
                }else {
                    $options[] = new MenuOption(TextFormat::AQUA.$language."\n".TextFormat::WHITE.TextFormat::BOLD.count(array_keys(Ryzer::$translations[$language]))." translations", new FormIcon("https://media.discordapp.net/attachments/779814956270223380/868901452649734304/276speakinghead_100550.png?width=410&height=410"));
                }
            }
        }
        parent::__construct(Ryzer::PREFIX."Language", "", $options, function (Player $player, int $selectedOption) use ($languages) : void{
            $language = $languages[$selectedOption];
            if(($obj = RyzerPlayerProvider::getRyzerPlayer($player->getName())) != null) {
                $obj->setLanguage($language);
                LanguageProvider::setLanguage($player->getName(), $language);
                $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('selected-language', $player->getName(), ['#language' => $language]));
            }
        });
    }
}