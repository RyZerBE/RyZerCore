<?php

namespace baubolp\core\util;

use pocketmine\math\Vector3;
use function max;
use function min;

class Vector3Utils {

    /**
     * @param Vector3 $vector3
     * @return string
     */
    public static function toString(Vector3 $vector3): string {
        return implode(":", [
            $vector3->x,
            $vector3->y,
            $vector3->z
        ]);
    }

    /**
     * @param string $vector3
     * @return Vector3
     */
    public static function fromString(string $vector3): Vector3 {
        $vector3 = explode(":", $vector3);
        return new Vector3(
            (float)($vector3[0] ?? 0),
            (float)($vector3[1] ?? 0),
            (float)($vector3[2] ?? 0),
        );
    }

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param Vector3 $vector3
     * @return bool
     */
    public static function inArea(Vector3 $pos1, Vector3 $pos2, Vector3 $vector3): bool {
        if($vector3->x <= min($pos1->x, $pos2->x) or $vector3->x >= max($pos1->x, $pos2->x)) return false;
        if($vector3->y <= min($pos1->y, $pos2->y) or $vector3->y >= max($pos1->y, $pos2->y)) return false;
        return $vector3->z > min($pos1->z, $pos2->z) and $vector3->z < max($pos1->z, $pos2->z);
    }

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param Vector3 $vector3
     * @return Vector3
     */
    public static function getNearestVector(Vector3 $pos1, Vector3 $pos2, Vector3 $vector3): Vector3 {
        $nearestVector = null;
        for ($x = min($pos1->x, $pos2->x); $x <= max($pos1->x, $pos2->x); $x++) {
            for ($z = min($pos1->z, $pos2->z); $z <= max($pos1->z, $pos2->z); $z++) {
                for ($y = min($pos1->y, $pos2->y); $y <= max($pos1->y, $pos2->y); $y++) {
                    $tempVector3 = new Vector3($x, $y, $z);
                    if(is_null($nearestVector) || $tempVector3->distance($vector3) <= $nearestVector->distance($vector3)) {
                        $nearestVector = $tempVector3;
                    }
                }
            }
        }
        return $nearestVector;
    }
}