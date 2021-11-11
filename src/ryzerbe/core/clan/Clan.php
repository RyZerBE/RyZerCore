<?php

namespace ryzerbe\core\clan;

use pocketmine\utils\TextFormat;

class Clan {
    public const CLOSE = 0;
    public const INVITE = 1;
    public const OPEN = 2;

    private string $clanName;
    private string $clanTag;
    private int $elo;
    private string $owner;

    public function __construct(string $clanName, string $clanTag, int $elo, string $owner){
        $this->clanName = $clanName;
        $this->clanTag = $clanTag;
        $this->elo = $elo;
        $this->owner = $owner;
    }

    public function getClanName(): string{
        return $this->clanName;
    }

    public function getClanTag(): string{
        return str_replace("&", TextFormat::ESCAPE, $this->clanTag);
    }

    public function getElo(): int{
        return $this->elo;
    }

    public function getOwner(): string{
        return $this->owner;
    }
}