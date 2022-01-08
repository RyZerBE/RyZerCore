<?php

namespace ryzerbe\core\provider;

use pocketmine\utils\SingletonTrait;
use function count;
use function explode;
use function filter_var;
use function gethostbyname;
use function in_array;
use function preg_match_all;
use function preg_replace;
use function str_contains;
use function str_ireplace;
use function str_replace;
use function strlen;
use function strtolower;
use function var_dump;

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
        "asshole",
        "fotze",
        "fötzchen",
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
        "huso",
        "wichser",
        "spasti",
        "spast",
        "pisser",
        "pissdich",
        "bumsen",
        "bitch"
    ];

    public const PROVOCATIONS = [
        "ez",
        "e2",
        "l2p",
        "l",
        "loser",
        "kek",
        "bg",
        "noob"
    ];

    public const REPLACE_WORDS = [
        "rushnation" => "bugnation",
        "l" => "besser als ich gg",
        "ll" => "besser als ich gg",
        "lll" => "besser als ich gg",
        "l2p" => "Ich muss glaube besser werden :/",
        "loser" => "ich bin voll schlecht bruh",
        "ez" => "voll gut gespielt",
        "e2" => "voll gut gespielt",
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
                var_dump("WORD: ".$FORBIDDEN_WORD);
            }
        }

        return $badWords;
    }

    /**
     * @param string $message
     * @return array
     */
    public function checkProvocation(string $message): array{
        $message = explode(" ", $this->cleanMessageForCheck(strtolower($message), [" ", "2"]));

        $provocations = [];
        foreach(self::PROVOCATIONS as $PROVOCATION) {
            if(in_array($PROVOCATION, $message)) {
                $provocations[] = $PROVOCATION;
            }
        }

        return $provocations;
    }

    /**
     * @param string $message
     * @return array
     */
    public function checkDomain(string $message): array{
        $message = explode(" ", $this->cleanMessageForCheck(strtolower(str_replace(",", ".", $message)), [" ", "."]));
        $advertisement = [];
        foreach($message as $word){
            if(str_contains($word, "ryzer.be")) continue;
            $checkDNS = gethostbyname($word);
            if(filter_var($word, FILTER_VALIDATE_URL) !== false
                || filter_var($checkDNS, FILTER_VALIDATE_IP) !== false){
                $advertisement[] = $word;
                var_dump("DOMAIN: $word");
            }
        }

        return $advertisement;
    }

    /**
     * return a string without duplicated characters
     *
     * @param string $message
     * @return string
     */
    public function replaceDuplicatedCharacters(string $message): string{
        return preg_replace('/(([^\d])\2\2)\2+/', '$1', $message);
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

            $message = str_ireplace($badWord, $replaceWord, $message);
        }

        return $message;
    }

    /**
     * remove useless chars
     *
     * @param string $message
     * @param array $notReplace
     * @return string
     */
    public function cleanMessageForCheck(string $message, array $notReplace = []): string{
        $removeChars = [
            "-", " ",
            "_", "+",
            ".", ",",
            "#", "~",
            "*", "$",
            "!", "§",
            "%", "&",
            "/", "(",
            ")", "=",
            "?", "`",
            ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0"]
        ];

        foreach($removeChars as $char){
            if(in_array($char, $notReplace)) continue;
            $message = str_replace($char, "", $message);
        }
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