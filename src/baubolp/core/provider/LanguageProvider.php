<?php


namespace baubolp\core\provider;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use mysqli;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class LanguageProvider
{

    /**
     * @param string $language
     */
    public static function createLanguage(string $language)
    {
        if(MySQLProvider::existConnection("Language")) {
            MySQLProvider::getSQLConnection("Language")->getSql()->query("CREATE TABLE IF NOT EXISTS `$language`(id INTEGER NOT NULL KEY AUTO_INCREMENT, messagekey varchar(64) NOT NULL, message TEXT NOT NULL)");
        }
    }

    /**
     * @param string $username
     * @return string
     */

    public static function getLanguage(string $username): string
    {
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($username)) != null) {
            if($obj->getLanguage() == null) {
                $result = MySQLProvider::getSQLConnection("Core")->getSql()->query("SELECT selected_language FROM PlayerLanguage WHERE playername='$username'");
                if($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $obj->setLanguage($row['selected_language']);
                    }
                }
            }
            return $obj->getLanguage();
        }
        return "English";
    }

    /**
     * @param string $username
     * @param string $language
     */
    public static function setLanguage(string $username, string $language): void
    {
        AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function (mysqli $mysqli) use ($language, $username){
            $mysqli->query("UPDATE PlayerLanguage SET selected_language='$language' WHERE playername='$username'");
        });
    }

    /**
     * @param string $key
     * @param string $username
     * @param array $replaces
     * @return string
     */
    public static function getMessageContainer(string $key, string $username, array $replaces = []): string
    {

        $language = LanguageProvider::getLanguage($username);

        if(empty(Ryzer::$translations[$language][$key])) {
            return $key;
        }
        $message = str_replace("&", TextFormat::ESCAPE, Ryzer::$translations[$language][$key]);
        $message = str_replace("#nl", "\n", $message);
        foreach (array_keys($replaces) as $key) {
            $message = str_replace($key, $replaces[$key], $message);
        }
        return $message;
    }

    public static function reloadLanguages(): void
    {
        Ryzer::$translations = [];
        self::loadLanguages();
    }

    public static function loadLanguages()
    {
        AsyncExecutor::submitMySQLAsyncTask("Languages", function (mysqli $mysqli) {
            $result = $mysqli->query("SHOW TABLES");
            $languages = [];
            while ($data = $result->fetch_assoc()) {
                $languages[] = $data['Tables_in_Languages'];
            }
            return $languages;
        }, function (Server $server, array $result){
            foreach ($result as $language) {
                LanguageProvider::loadKeys($language);
            }
        });
    }

    /**
     * @param string $language
     */
    public static function loadKeys(string $language)
    {
        AsyncExecutor::submitMySQLAsyncTask("Languages", function (mysqli $mysqli) use ($language){
            $result = $mysqli->query("SELECT * FROM ".$language);
            $translation = [];
            if($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    $translation[$data['messagekey']] = $data['message'];
                }
            }
            return $translation;
        }, function (Server $server, array $translations) use ($language){
            $i = 0;
            foreach ($translations as $key => $message) {
                Ryzer::$translations[$language][$key] = $message;
                $i++;
            }

            $server->getLogger()->info(TextFormat::GOLD.$language.TextFormat::DARK_AQUA." were loaded!\n    ".TextFormat::DARK_GRAY."-> ".TextFormat::GOLD.$i." translations fetched!");
        });
    }

    /**
     * @param string $language
     * @param string $key
     * @param string $translation
     */
    public static function addKey(string $language, string $key, string $translation)
    {
        AsyncExecutor::submitMySQLAsyncTask("Languages", function (mysqli $mysqli) use ($translation, $key, $language){
            $mysqli->query("INSERT INTO `$language`(`messagekey`, `message`) VALUES ('$key', '$translation')");
        });
    }

    /**
     * @param string $language
     * @param string $key
     */
    public static function removeKey(string $language, string $key)
    {
        AsyncExecutor::submitMySQLAsyncTask("Languages", function (mysqli $mysqli) use ($translation, $key, $language){
            $mysqli->query("DELETE FROM `$language` WHERE messagekey='$key'");
        });
    }
}