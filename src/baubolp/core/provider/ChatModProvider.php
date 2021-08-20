<?php


namespace baubolp\core\provider;


use baubolp\core\player\RyzerPlayer;

class ChatModProvider
{

    const WAIT_TIME = 1;
    /** @var array  */
    public static array $badWords = [
        "L2Play" => "Du spielst mega gut",
        "Hitler" => null,
        "Jude" => null,
        "Ficken" => null,
        "Fick dich" => null,
        "Opfer" => null,
        "Hure" => null,
        "Huan" => null,
        "Hurensohn" => null,
        "Arsch" => null,
        "Drecks Server" => "Fresher Server",
        "Sex" => null,
        "Weed" => null,
        "Wixxer" => null,
        "Wixer" => null,
        "Wichser" => null,
        "Misgeburt" => null,
        "Missgeburt" => null,
        "Fehlgeburt" => null,
        "Schlampe" => null,
        "Negger" => null,
        "Negga" => null,
        "Fick" => null,
        "gefickt" => null,
        "fuck" => null,
        "Lappen" => null,
        "Heil Hitler" => null,
        "Mutterficker" => null,
        "RushNation" => null,
        "RusherHub" => null,
        "NinjaHub" => null,
        "StimoMC" => null,
        "NetherGames" => null,
        "Multilabs" => null
    ];

    public static array $whitelistedURLS = [
        "ryzer.be",
        "ryzer.be/pma",
        "builder.ryzer.be",
        "staff.ryzer.be",
        "designer.ryzer.be",
        "architekt.ryzer.be",
        "developer.ryzer.be",
        "discord.ryzer.be",
        "chatlog.ryzer.be",
        "instagram.ryzer.be",
        "apply.ryzer.be",
        "twitter.ryzer.be",
        "youtube.ryzer.be",
        "yt.ryzer.be"
    ];
    /** @var array  */
    public static array $wait = [];

    /**
     * @param string $playerName
     * @return bool
     */
    public static function mustWait(string $playerName): bool
    {
        return isset(self::$wait[$playerName]);
    }

    /**
     * @param string $playerName
     */
    public static function addWaiter(string $playerName)
    {
        self::$wait[$playerName] = time() + self::WAIT_TIME;
    }

    /**
     * @param string $message
     * @return bool
     */
    public static function checkCaps(string $message): bool
    {
        preg_match_all('/[A-Z]/', $message, $matches);
        $caps = count($matches[0]);
       // var_dump($matches[0]);
        return $caps >= strlen($message) / 2;
    }

    /**
     * @param RyzerPlayer $player
     * @param string $text
     * @return bool
     */
    public static function equalsLastMessageWithText(RyzerPlayer $player, string $text): bool
    {
        return strtolower($player->getLastMessage()) == strtolower($text);
    }

    /**
     * @param string $message
     * @return bool
     */
    public static function isUrl(string $message)
    {
        foreach (explode(" ", $message) as $word) {
            if(filter_var($word, FILTER_VALIDATE_URL) !== false) {
              if(!in_array($message, self::$whitelistedURLS))
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $message
     * @return bool
     */
    public static function isIp(string $message): bool
    {
        foreach (explode(" ", $message) as $word) {
            if(filter_var($word, FILTER_VALIDATE_IP) !== false && filter_var($word, FILTER_VALIDATE_DOMAIN) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $message
     * @return bool|string|string[]
     */
    public static function isBadWord(string $message)
    {
        foreach (array_keys(self::$badWords) as $key) {
            if (str_contains(strtolower($message), strtolower($key))) {
                if (self::$badWords[$key] != null)
                    return str_replace($key, self::$badWords[$key], $message);
                else
                    return true;
            }
        }
        return false;
    }
}