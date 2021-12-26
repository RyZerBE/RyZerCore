<?php

namespace ryzerbe\core\player\chatmod;

use ryzerbe\core\provider\ChatModProvider;
use function microtime;

class PlayerChatMod {

    public string $lastMessage = "BroxstarIstFett";
    public int|float $lastMessageTime;


    public function __construct(){
        $this->lastMessageTime = microtime(true);
    }

    /**
     * @return float|int
     */
    public function getLastMessageTime(): float|int{
        return $this->lastMessageTime;
    }

    /**
     * @param bool $clean
     * @return string
     */
    public function getLastMessage(bool $clean = true): string{
        return $clean === true ? ChatModProvider::getInstance()->cleanMessageForCheck($this->lastMessage) : $this->lastMessage;
    }

    public function isSpamming(): bool{
        return ($this->lastMessageTime + 1) > microtime(true);
    }
}