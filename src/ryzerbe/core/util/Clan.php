<?php

namespace ryzerbe\core\util;

use pocketmine\utils\TextFormat;

class Clan {

    const CLOSE = 0;
    const INVITE = 1;
    const OPEN = 2;

    /** @var string  */
    private string $clanName;
    /** @var string  */
    private string $clanTag;
    /** @var int  */
    private int $elo;
    /** @var string */
    private string $owner;

    /**
     * @param string $clanName
     * @param string $clanTag
     * @param int $elo
     * @param string $owner
     */
    public function __construct(string $clanName, string $clanTag, int $elo, string $owner){
        $this->clanName = $clanName;
        $this->clanTag = $clanTag;
        $this->elo = $elo;
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    public function getClanName(): string{
        return $this->clanName;
    }

    /**
     * @return string
     */
    public function getClanTag(): string{
        return str_replace("&", TextFormat::ESCAPE, $this->clanTag);
    }

    /**
     * @return int
     */
    public function getElo(): int{
        return $this->elo;
    }

    /**
     * @return string
     */
    public function getOwner(): string{
        return $this->owner;
    }
}