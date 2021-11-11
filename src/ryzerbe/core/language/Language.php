<?php

namespace ryzerbe\core\language;

use mysqli;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\util\async\AsyncExecutor;
use function array_keys;
use function count;
use function str_replace;

class Language {
    private string $languageName;
    private array $translations;

    public function __construct(string $languageName, array $translations = []){
        $this->languageName = $languageName;
        $this->translations = $translations;
        $this->loadTranslationsFromDatabase();
    }

    public function loadTranslationsFromDatabase(){
        $languageName = $this->getLanguageName();
        AsyncExecutor::submitMySQLAsyncTask("Languages", function(mysqli $mysqli) use ($languageName): array{
            $result = $mysqli->query("SELECT * FROM ".$languageName);
            $translation = [];
            if($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    $translation[$data['messagekey']] = $data['message'];
                }
            }
            return $translation;
        }, function(Server $server, array $translations) use ($languageName){
            $language = LanguageProvider::getLanguage($languageName);
            if($language === null) {
                $server->getLogger()->error("Language $languageName not found!");
                return;
            }
            foreach($translations as $key => $message) {
                $this->addTranslation($key, $message);
            }

            $server->getLogger()->info(TextFormat::GOLD.$languageName.TextFormat::DARK_AQUA." were loaded and ".TextFormat::GOLD.count($this->getTranslations())." translations ".TextFormat::GREEN." fetched!");
        });
    }

    public function getLanguageName(): string{
        return $this->languageName;
    }

    public function getTranslations(): array{
        return $this->translations;
    }

    public function addTranslation(string $key, string $message, bool $mysql = false){
        $this->translations[$key] = $message;
        if($mysql) {
            $languageName = $this->getLanguageName();
            AsyncExecutor::submitMySQLAsyncTask("Languages", function(mysqli $mysqli) use ($languageName, $key, $message): void{
                $mysqli->query("INSERT INTO `$languageName`(`messagekey`, `message`) VALUES ('$key', '$message')");
            });
        }
    }

    public function removeTranslation(string $key, bool $mysql = false){
        unset($this->translations[$key]);
        if($mysql) {
            $languageName = $this->getLanguageName();
            AsyncExecutor::submitMySQLAsyncTask("Languages", function(mysqli $mysqli) use ($languageName, $key): void{
                $mysqli->query("DELETE FROM `$languageName` WHERE messagekey='$key'");
            });
        }
    }

    public function getMessageByKey(string $key): ?String{
        return $this->translations[$key] ?? null;
    }

    public function getMessage(string $key, array $replaces = [], bool $noEscape = false): string{
        $message = $this->getMessageByKey($key);
        if($message === null) {
            return "There is no translation with the key \"".$key."\"";
        }
        $message = str_replace("#nl", "\n", $message);
        foreach (array_keys($replaces) as $key) {
            $message = str_replace($key, $replaces[$key], $message);
        }

        if($noEscape) return $message;
        $message = str_replace("&", TextFormat::ESCAPE, $message);
        return $message;
    }

    public function getTranslation(string $key, array $replaces = []): string{
        $message = $this->getMessageByKey($key);
        if($message === null) {
            return $key;
        }

        $message = str_replace("&", TextFormat::ESCAPE, $message);
        $message = str_replace("#nl", "\n", $message);
        $message = str_replace("#newLine", "\n", $message);
        foreach (array_keys($replaces) as $key) {
            $message = str_replace($key, $replaces[$key], $message);
        }
        return $message;
    }
}