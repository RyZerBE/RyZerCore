<?php

namespace baubolp\core\util;

use pocketmine\level\Location;
use pocketmine\Server;

class LocationUtils {

    /**
     * @param Location $location
     * @return string
     */
    public static function toString(Location $location): string {
        return implode(":", [
            $location->x,
            $location->y,
            $location->z,
            $location->yaw,
            $location->pitch,
            $location->getLevel()->getFolderName()
        ]);
    }

    /**
     * @param string $location
     * @return Location
     */
    public static function fromString(string $location): Location {
        $location = explode(":", $location);
        return new Location(
            (float)($location[0] ?? 0),
            (float)($location[1] ?? 0),
            (float)($location[2] ?? 0),
            (float)($location[3] ?? 0),
            (float)($location[4] ?? 0),
            (isset($location[5]) ? Server::getInstance()->getLevelByName($location[5]) : Server::getInstance()->getDefaultLevel())
        );
    }
}