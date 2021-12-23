<?php

namespace ryzerbe\core\player\data;

class NickInfo {

    public function __construct(private string $nickName, private string $skin, private int $level){}

    /**
     * @return string
     */
    public function getNickName(): string{
        return $this->nickName;
    }

    /**
     * @return int
     */
    public function getLevel(): int{
        return $this->level;
    }

    /**
     * @return string
     */
    public function getSkin(): string{
        return $this->skin;
    }
}