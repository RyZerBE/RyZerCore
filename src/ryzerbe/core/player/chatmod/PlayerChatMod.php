<?php

namespace ryzerbe\core\player\chatmod;

use function microtime;

class PlayerChatMod {

    public int|float $lastMessage;

    public function __construct(){
        $this->lastMessage = microtime(true);
    }
}