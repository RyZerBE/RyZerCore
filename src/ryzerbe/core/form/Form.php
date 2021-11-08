<?php

namespace ryzerbe\core\form;

use pocketmine\Player;

abstract class Form {

    /**
     * @param Player $player
     * @param array $extraData
     */
    abstract public static function onOpen(Player $player, array $extraData = []): void;
}