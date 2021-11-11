<?php

namespace ryzerbe\core\util;

class TaskUtils {
    public static function secondsToTicks(int $seconds): int{
        return $seconds * 20;
    }

    public static function minutesToTicks(int $minutes): int{
        return $minutes * 1200;
    }
}