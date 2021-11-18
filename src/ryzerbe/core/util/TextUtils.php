<?php

declare(strict_types=1);

namespace ryzerbe\core\util;

use pocketmine\utils\TextFormat;
use function count;
use function explode;
use function round;
use function str_repeat;
use function strlen;

final class TextUtils {
    /**
     * //TODO: Still not perfect but I´m too lazy atm :/
     */
    public static function formatEol(string $text): string {
        $textParts = explode("\n", $text);
        if(count($textParts) <= 1) return $text;

        $longestLine = -1;
        foreach($textParts as $textPart) {
            $length = strlen(TextFormat::clean($textPart));
            if($length > $longestLine) $longestLine = $length;
        }

        $result = "";
        foreach($textParts as $textPart) {
            $length = strlen(TextFormat::clean($textPart));
            if(!empty($result)) $result .= "\n";
            $times = (int)round(($longestLine - $length) / 2, 0, PHP_ROUND_HALF_DOWN);
            $result .= str_repeat(" ", ($times <= 0 ? 0 : $times)) . $textPart;
        }
        return $result;
    }
}