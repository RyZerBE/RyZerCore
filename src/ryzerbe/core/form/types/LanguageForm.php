<?php

namespace ryzerbe\core\form\types;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\RyZerBE;
use function array_keys;
use function count;
use function round;
use function strtolower;

class LanguageForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $languages = array_keys(LanguageProvider::$languages);
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($ryzerPlayer === null) return;
        $form = new SimpleForm(function(Player $player, $data) use ($ryzerPlayer): void{
            if($data === null) return;
            switch($data){
                case "reload":
                    LanguageProvider::fetchLanguages();
                    $player->sendMessage(RyZerBE::PREFIX . "All languages reloaded!");
                    break;
                default:
                    $language = $data;
                    if($player->hasPermission("ryzer.admin")){
                        $form = new SimpleForm(function(Player $player, $data) use ($ryzerPlayer, $language): void{
                            if($data === null) return;
                            switch($data){
                                case "choose":
                                    $ryzerPlayer->setLanguage($language, true);
                                    $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer('selected-language', $player->getName(), ['#language' => $language]));
                                    break;
                                case "edit":
                                    $form = new SimpleForm(function(Player $player, $data) use ($language): void{
                                        if($data === null) return;
                                        switch($data){
                                            case "add":
                                                $form = new CustomForm(function(Player $player, $data) use ($language): void{
                                                    if($data === null) return;
                                                    $language = LanguageProvider::getLanguage($language);
                                                    $language?->addTranslation($data["key"], $data["translation"], true);
                                                    $player->sendMessage(RyZerBE::PREFIX . TextFormat::GREEN . "Übersetzung hinzugefügt.");
                                                });
                                                $form->addInput("Language Key", "", "", "key");
                                                $form->addInput("Translation", "", "", "translation");
                                                $form->setTitle(TextFormat::GOLD . $language);
                                                $form->sendToPlayer($player);
                                                break;
                                            case "delete":
                                                $form = new CustomForm(function(Player $player, $data) use ($language): void{
                                                    if($data === null) return;
                                                    $language = LanguageProvider::getLanguage($language);
                                                    $language?->removeTranslation($data["key"], true);
                                                    $player->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Übersetzung entfernt.");
                                                });
                                                $form->addInput("Language Key", "", "", "key");
                                                $form->setTitle(TextFormat::GOLD . $language);
                                                $form->sendToPlayer($player);
                                                break;
                                            case "translate":
                                                $form = new SimpleForm(function(Player $player, $data) use ($language): void{
                                                    if($data === null) return;
                                                    $key = $data;
                                                    $form = new CustomForm(function(Player $player, $data) use ($language, $key): void{
                                                        if($data === null) return;
                                                        $language = LanguageProvider::getLanguage($language);
                                                        $language?->addTranslation($key, $data["translation"], true);
                                                        $player->sendMessage(RyZerBE::PREFIX . TextFormat::GREEN . "Erfolgreich übersetzt.");
                                                    });
                                                    $form->addInput(LanguageProvider::getMessage($data, "Deutsch") . "\n" . "Bitte tippe die Übersetzung für die Sprache " . $language . " ein:", "", "", "translation");
                                                    $form->sendToPlayer($player);
                                                });
                                                $form->setContent("Folgende Keys sind in der Sprache " . $language . " noch nicht übersetzt.");
                                                $german = LanguageProvider::getLanguage("Deutsch");
                                                $language = LanguageProvider::getLanguage($language);
                                                foreach($german->getTranslations() as $key => $translation){
                                                    if($language->getMessageByKey($key) !== null) continue;
                                                    $form->addButton($key, -1, "", $key);
                                                }
                                                $form->setTitle(TextFormat::GOLD . $language->getLanguageName());
                                                $form->sendToPlayer($player);
                                                break;
                                        }
                                    });
                                    $form->setTitle(TextFormat::GOLD . $language);
                                    $form->addButton("Add translation", 0, "textures/ui/book_edit_pressed", "add");
                                    $form->addButton("Delete translation", 0, "textures/ui/redX1", "delete");
                                    if($language !== "Deutsch"){
                                        $form->addButton("Translate from German", 0, "textures/ui/worldsIcon", "translate");
                                    }
                                    $form->sendToPlayer($player);
                                    break;
                            }
                        });
                        $form->setTitle(TextFormat::GOLD . $language);
                        $form->addButton(TextFormat::GREEN . "Auswählen", 0, "textures/ui/confirm.png", "choose");
                        $form->addButton(TextFormat::RED . "Editieren", 0, "textures/ui/icon_setting", "edit");
                        $form->sendToPlayer($player);
                        return;
                    }
                    $ryzerPlayer->setLanguage($language, true);
                    $player->sendMessage(RyZerBE::PREFIX . LanguageProvider::getMessageContainer('selected-language', $player->getName(), ['#language' => $language]));
            }
        });
        $german = LanguageProvider::getLanguage("Deutsch");
        foreach($languages as $language){
            $languageObj = LanguageProvider::getLanguage($language);
            $percentTranslated = round(((count($languageObj->getTranslations()) * 100) / count($german->getTranslations())));
            if(strtolower($language) == strtolower($ryzerPlayer->getLanguageName())){
                $form->addButton(TextFormat::AQUA . TextFormat::BOLD . $language . "\n" . TextFormat::WHITE . TextFormat::BOLD . $percentTranslated . "%% translated", 1, "https://media.discordapp.net/attachments/779814956270223380/868901452649734304/276speakinghead_100550.png?width=410&height=410", $language);
            }
            else{
                $form->addButton(TextFormat::AQUA . $language . "\n" . TextFormat::WHITE . $percentTranslated . "%% translated", 1, "https://media.discordapp.net/attachments/779814956270223380/868901452649734304/276speakinghead_100550.png?width=410&height=410", $language);
            }
        }
        if($player->hasPermission("language.admin")) $form->addButton(TextFormat::RED . "Reload Languages", 0, "textures/ui/refresh", "reload");
        $form->sendToPlayer($player);
    }
}