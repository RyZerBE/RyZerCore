<?php

namespace ryzerbe\core\provider;

use function str_contains;
use function str_replace;
use function strlen;
use function strtolower;

class ChatModProvider implements RyZerProvider {

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
        "stimomc"
    ];

    /**
     * return blocked word
     * null = alright
     *
     * @param string $message
     * @return array|null
     */
    public function checkForbiddenWord(string $message): ?string{
        $message = $this->cleanMessageForCheck($message);

        foreach(self::FORBIDDEN_WORDS as $FORBIDDEN_WORD) {
            if(str_contains(strtolower($message), $FORBIDDEN_WORD)) return $FORBIDDEN_WORD;
        }

        return null;
    }

    public function cleanMessageForCheck(string $message){
        $removeChars = [
            " ", "-",
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

        foreach($removeChars as $char) $message = str_replace($char, "", $message);
        return $message;
    }

    /**
     * true -> alright
     * false -> more than 50% caps
     * @param string $message
     * @return bool
     */
    public function checkCaps(string $message): bool{
        preg_match_all("/[A-Z]/", $message, $matches);

        //todo: return value
    }
}