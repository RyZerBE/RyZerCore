<?php

namespace ryzerbe\core\provider;

use pocketmine\utils\SingletonTrait;
use function array_keys;
use function array_map;
use function array_values;
use function str_replace;

class ChatEmojiProvider implements RyZerProvider {
    use SingletonTrait;

    public const EMOJIS = [
        "happy" => "",
        "neutral" => "",
        "pog" => "",
        "love" => "",
        "tear" => "",
        "thinking" => "",
        "confused" => "",
        "sunglasses" => "",
        "smirk" => "",
        "clown" => "",
        "woozy" => "",
        "salt" => "",
        "cry" => "",
        "sherlock" => "",
        "surprised" => "",
        "derp" => "",
        "money" => "",
        "scream" => "",
        "sleepy" => "",
        "trash" => "",
        "thinkingc" => "",
        "hmmm" => "",
        "feelsbadman" => "",
        "penguin" => "",
        "yocker" => "",
        "angry" => ""
    ];

    /**
     * return the message back with emojis
     *
     * @param string $message
     * @return string
     */
    public function replaceKeys(string $message): string{
        return str_replace(array_map(function(string $key): string{
            return ":".$key.":";
        }, array_keys(self::EMOJIS)), array_values(self::EMOJIS), $message);
    }
    //todo: implement animated Emojis (in work..)
}