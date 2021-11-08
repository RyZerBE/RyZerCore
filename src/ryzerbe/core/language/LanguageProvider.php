<?php

namespace ryzerbe\core\language;

use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\util\async\AsyncExecutor;

class LanguageProvider {

    /** @var Language[]  */
    public static array $languages = [];

    /**
     * @param Language $language
     */
    public static function addLanguage(Language $language){
        self::$languages[$language->getLanguageName()] = $language;
    }

    /**
     * @param string $languageName
     * @return Language|null
     */
    public static function getLanguage(string $languageName): ?Language{
        return self::$languages[$languageName] ?? null;
    }

    public static function fetchLanguages(): void{
        AsyncExecutor::submitMySQLAsyncTask("Languages", function (mysqli $mysqli) {
            $result = $mysqli->query("SHOW TABLES");
            $languages = [];
            while ($data = $result->fetch_assoc()) {
                $languages[] = $data['Tables_in_Languages'];
            }
            return $languages;
        }, function (Server $server, array $result){
            foreach ($result as $language) {
                $language = new Language($language);
                LanguageProvider::addLanguage($language);
            }
        });
    }

    /**
     * @param string $key
     * @param string|Player $player
     * @param array $replaces
     * @return string
     */
    public static function getMessageContainer(string $key, string|Player $player, array $replaces = []): string{
        if($player instanceof Player) $player = $player->getName();
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($ryzerPlayer === null) return $key;

        $language = self::getLanguage($ryzerPlayer->getLanguageName());
        if($language === null) return $key;

        return $language->getTranslation($key, $replaces);
    }

    /**
     * @param string $key
     * @param string $languageName
     * @return string
     */
    public static function getMessage(string $key, string $languageName){
        $language = self::getLanguage($languageName);
        if($language === null) return $key;

        return $language->getMessage($key, [], true);
    }
}