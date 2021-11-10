<?php

namespace ryzerbe\core\util;

class TaskUtils {

    /**
     * @param int $seconds
     * @return int
     */
    public static function secondsToTicks(int $seconds): int{
        return $seconds * 20;
    }

    /**
     * @param int $minutes
     * @return int
     */
    public static function minutesToTicks(int $minutes): int{
        return $minutes * 1200;
    }
}