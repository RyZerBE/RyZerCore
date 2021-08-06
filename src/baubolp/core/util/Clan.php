<?php

namespace baubolp\core\util;

class Clan {

    /** @var string  */
    private $clanName;
    /** @var string  */
    private $clanTag;
    /** @var int  */
    private $elo = 100;
    /** @var bool */
    private $isOwner;
    /**
     * @param string $clanName
     * @param string $clanTag
     * @param int $elo
     * @param bool $isOwner
     */
    public function __construct(string $clanName, string $clanTag, int $elo, bool $isOwner = false){
        $this->clanName = $clanName;
        $this->clanTag = $clanTag;
        $this->elo = $elo;
        $this->isOwner = $isOwner;
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
        return $this->clanTag;
    }

    /**
     * @return int
     */
    public function getElo(): int{
        return $this->elo;
    }

    /**
     * @return bool
     */
    public function isOwner(): bool{
        return $this->isOwner;
    }
}