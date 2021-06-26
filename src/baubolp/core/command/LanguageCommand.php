<?php


namespace baubolp\core\command;


use baubolp\core\form\LanguageForm;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class LanguageCommand extends Command
{

    public function __construct()
    {
        parent::__construct("language", "select your language", "/language", ["lang"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(isset($args[0])) {
            $language = LanguageProvider::getLanguage($sender->getName());
            if($args[0] == "addtranslation") {
                if($sender->hasPermission("languages.edit")) {
                    if (isset($args[1]) && isset($args[2])) {
                        $key = $args[1];
                        unset($args[0]);
                        unset($args[1]);
                        $translation = implode(" ", $args);
                        LanguageProvider::addKey($language, $key, $translation);
                        $sender->sendMessage(Ryzer::PREFIX . TextFormat::GREEN . "Successfully set the translation " . TextFormat::AQUA . $translation . TextFormat::GREEN . " to the key " . TextFormat::AQUA . $key);
                    } else {
                        $sender->sendMessage(Ryzer::PREFIX . TextFormat::YELLOW . "Syntax error: /language addtranslation [KEY] [TRANSLATION]");
                    }
                }
            } else if($args[0] == "removetranslation") {
                if($sender->hasPermission("languages.edit")) {
                    if (isset($args[1])) {
                        $sender->sendMessage(Ryzer::PREFIX . TextFormat::GREEN . "The message with the key " . $args[1], " was removed!");
                        LanguageProvider::removeKey($language, $args[1]);
                    } else {
                        $sender->sendMessage(Ryzer::PREFIX . TextFormat::YELLOW . "Syntax error: /language removetranslation [KEY]");
                    }
                }
            } else if($args[0] == "testkey") {
                if($sender->hasPermission("languages.edit")) {
                    if (isset($args[1])) {
                        $sender->sendMessage(Ryzer::PREFIX . $args[1] . ": " . LanguageProvider::getMessageContainer($args[1], $sender->getName()));
                    } else {
                        $sender->sendMessage("/language testkey [KEY]");
                    }
                }
            }elseif($args[0] == "reload") {
                if($sender->hasPermission("languages.reload")) {
                    LanguageProvider::reloadLanguages();
                    $sender->sendMessage(Ryzer::PREFIX."All translations are reloaded!");
                }
            }elseif($args[0] == "getkeys") {
                if(isset($args[1])) {
                    if(is_numeric($args[1])) {
                        $keys = Ryzer::$translations['Deutsch'];
                        $message = "";
                        for($i = 0; $i < 10 * $args[1]; $i++) {
                            $message .= TextFormat::AQUA.array_keys($keys)[$i].TextFormat::GRAY." = ".TextFormat::RESET.array_values($keys)[$i]."\n";
                        }
                        $sender->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Alle bisher registrierten §bKeys §7mit §bÜbersetzung§7: ");

                        $sender->sendMessage($message);
                    }
                }else {
                    $keys = Ryzer::$translations['Deutsch'];
                    $message = "";
                    foreach (array_keys($keys) as $key) {
                        $message .= TextFormat::AQUA.$key.TextFormat::GRAY." = ".TextFormat::RESET.$keys[$key]."\n";
                    }
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::GRAY."Alle bisher registrierten §bKeys §7mit §bÜbersetzung§7: ");
                    $sender->sendMessage($message);
                }
            }else {
                $sender->sendMessage("/language addtranslation/removetranslation/testkey");
            }
            return;
        }
        $sender->sendForm(new LanguageForm($sender->getName()));
    }

}