<?php

namespace ryzerbe\core\provider;

use pocketmine\utils\SingletonTrait;
use function count;
use function explode;
use function in_array;
use function str_contains;
use function str_replace;
use function strlen;
use function strtolower;

class ChatModProvider implements RyZerProvider {
    use SingletonTrait;

    const MUST_HAVE_PERCENT_UPPER_CASES = 50;//%

    public const FORBIDDEN_WORDS = [
        "hure",
        "hurensohn",
        "hurentochter",
        "nutte",
        "bastard",
        "wixxer",
        "wixer",
        "ficker",
        "hitler",
        "mutterficker",
        "motherfucker",
        "fick",
        "amana",
        "sikerim",
        "arschloch",
        "fotze",
        "vagina",
        "penis",
        "gangbang",
        "porn",
        "fresse",
        "schlampe",
        "nigger",
        "nigga",
        "neger",
        "negger",
        "fuck",
        "missgeburt",
        "opfer",
        "salvation",
        "rushnation",
        "stimomc",
        "bulle",
        "hs",
        "huso"
    ];

    public const PROVOCATIONS = [
        "ez",
        "l",
        "kek",
        "bg",
    ];

    public const REPLACE_WORDS = [
        "rushnation" => "bugnation",
        "l" => "besser als ich lol",
        "ll" => "besser als ich lol",
        "lll" => "besser als ich lol",
        "llll" => "besser als ich lol",
        "ez" => "voll gut",
        "bg" => "Gut gespielt!"
    ];

    /**
     * return blocked words
     * null = alright
     *
     * @param string $message
     * @return array
     */
    public function checkForbiddenWord(string $message): array{
        $message = $this->cleanMessageForCheck($message);

        $badWords = [];
        foreach(self::FORBIDDEN_WORDS as $FORBIDDEN_WORD) {
            if(str_contains(strtolower($message), $FORBIDDEN_WORD)) {
                $badWords[] = $FORBIDDEN_WORD;
            }
        }

        return $badWords;
    }

    /**
     * @param string $message
     * @return array
     */
    public function checkProvocation(string $message): array{
        $message = explode(" ", $this->cleanMessageForCheck(strtolower($message), false));

        $provocations = [];
        foreach(self::PROVOCATIONS as $PROVOCATION) {
            if(in_array($PROVOCATION, $message)) {
                $provocations[] = $PROVOCATION;
            }
        }

        return $provocations;
    }

    /**
     * false = there is no word to replace
     * in other case it will return the replaced string
     *
     * @param string $message
     * @param array $badWords
     * @return bool|string
     */
    public function replaceBadWords(string $message, array $badWords): bool|string{
        foreach($badWords as $badWord) {
            $replaceWord = self::REPLACE_WORDS[$badWord] ?? null;
            if($replaceWord === null) return false;

            $message = str_replace($badWord, $replaceWord, $message);
        }

        return $message;
    }

    /**
     * remove useless chars
     *
     * @param string $message
     * @param bool $replaceSpace
     * @return string
     */
    public function cleanMessageForCheck(string $message, bool $replaceSpace = true): string{
        $removeChars = [
            "-",
            "_", "+",
            ".", ",",
            "#", "~",
            "*", "$",
            "!", "§",
            "%", "&",
            "/", "(",
            ")", "=",
            "?", "`"
        ];

        if($replaceSpace) $removeChars[] = " ";

        foreach($removeChars as $char) $message = str_replace($char, "", $message);
        return $message;
    }

    /**
     * false -> alright
     * true -> more than MUST_HAVE_PERCENT_UPPER_CASE caps
     * @param string $message
     * @return bool
     */
    public function checkCaps(string $message): bool{
        if(strlen($message) < 4) return false;
        preg_match_all("/[A-Z]/", $message, $matches);
        $messageLength = strlen($message);
        return ((count($matches[0]) * 100) / $messageLength) >= self::MUST_HAVE_PERCENT_UPPER_CASES;
    }
}