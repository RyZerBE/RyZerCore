<?php


namespace baubolp\core\form;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\form\FormIcon;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function count;

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
            if($obj->getPlayer()->hasPermission("language.edit"))
                $options[] = new MenuOption(TextFormat::RED."Reload Languages", new FormIcon("textures/ui/refresh", FormIcon::IMAGE_TYPE_PATH));
        }
        parent::__construct(Ryzer::PREFIX."Language", "", $options, function (Player $player, int $selectedOption) use ($languages) : void{
            if($selectedOption === count($languages)) {
                LanguageProvider::reloadLanguages();
                $player->sendMessage(Ryzer::PREFIX."Erfolgreich neugeladen!");
                return;
            }
            $language = $languages[$selectedOption];
            if(($obj = RyzerPlayerProvider::getRyzerPlayer($player->getName())) != null) {
                if($obj->getPlayer()->hasPermission("language.edit")) {
                    $form = new SimpleForm(function(Player $player, $data) use ($obj, $language): void{
                        if($data === null) return;

                        switch($data) {
                            case "choose":
                                $obj->setLanguage($language);
                                LanguageProvider::setLanguage($player->getName(), $language);
                                $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('selected-language', $player->getName(), ['#language' => $language]));
                                break;
                            case "edit":
                                $form = new SimpleForm(function(Player $player, $data) use ($language): void{
                                   if($data === null) return;

                                   switch($data) {
                                       case "add":
                                           $form = new CustomForm(function(Player $player, $data) use ($language): void{
                                                if($data === null) return;

                                                LanguageProvider::addKey($language, $data["key"], $data["translation"]);
                                                $player->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Übersetzung hinzugefügt.");
                                           });

                                           $form->addInput("Language Key", "", "", "key");
                                           $form->addInput("Translation", "", "", "translation");
                                           $form->setTitle(TextFormat::GOLD.$language);
                                           $form->sendToPlayer($player);
                                           break;
                                       case "delete":
                                           $form = new CustomForm(function(Player $player, $data) use ($language): void{
                                               if($data === null) return;

                                               LanguageProvider::removeKey($language, $data["key"]);
                                               $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Übersetzung entfernt.");
                                           });

                                           $form->addInput("Language Key", "", "", "key");
                                           $form->setTitle(TextFormat::GOLD.$language);
                                           $form->sendToPlayer($player);
                                           break;
                                       case "translate":
                                           $form = new SimpleForm(function(Player $player, $data) use ($language): void{
                                               if($data === null) return;
                                               $key = $data;
                                               $form = new CustomForm(function(Player $player, $data) use ($language, $key): void{
                                                  if($data === null) return;

                                                  LanguageProvider::addKey($language, $key, $data["translation"]);
                                                   $player->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."Erfolgreich übersetzt.");
                                               });

                                               $form->addInput(LanguageProvider::getTranslation($data, "Deutsch", [], true)."\n"."Bitte tippe die Übersetzung für die Sprache ".$language." ein:", "", "", "translation");
                                               $form->sendToPlayer($player);
                                           });

                                           $form->setContent("Folgende Keys sind in der Sprache ".$language." noch nicht übersetzt.");
                                           foreach(Ryzer::$translations["Deutsch"] as $key => $translation) {
                                               if(isset(Ryzer::$translations[$language][$key])) continue;
                                               $form->addButton($key, -1, "", $key);
                                           }
                                           $form->setTitle(TextFormat::GOLD.$language);
                                           $form->sendToPlayer($player);
                                           break;
                                   }
                                });

                                $form->setTitle(TextFormat::GOLD.$language);
                                $form->addButton("Add translation", 0, "textures/ui/book_edit_pressed", "add");
                                $form->addButton("Delete translation", 0, "textures/ui/redX1", "delete");
                                if($language != "Deutsch")
                                $form->addButton("Translate from German", 0, "textures/ui/worldsIcon", "translate");
                                $form->sendToPlayer($player);

                                break;
                        }
                    });

                    $form->setTitle(TextFormat::GOLD.$language);
                    $form->addButton(TextFormat::GREEN."Auswählen", 0, "textures/ui/confirm.png", "choose");
                    $form->addButton(TextFormat::RED."Editieren", 0, "textures/ui/icon_setting", "edit");
                    $form->sendToPlayer($player);
                    return;
                }
                $obj->setLanguage($language);
                LanguageProvider::setLanguage($player->getName(), $language);
                $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('selected-language', $player->getName(), ['#language' => $language]));
            }
        });
    }
}