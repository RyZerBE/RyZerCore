<?php


namespace baubolp\core\util;


class Rank
{
    /** @var int */
    private int|string $rankName;
    /** @var string */
    private string $nameTag;
    /** @var string */
    private string $chatPrefix;
    /** @var string  */
    private string $color;
    /** @var array */
    private array $permissions;
    /** @var int */
    private int $joinPower;

    public function __construct(string $rankName, string $nameTag, string $chatPrefix, array $permissions, string $color, int $joinPower)
    {
        $this->rankName = $rankName;
        $this->joinPower = $joinPower;
        $this->chatPrefix = $chatPrefix;
        $this->permissions = $permissions;
        $this->nameTag = $nameTag;
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getChatPrefix(): string
    {
        return $this->chatPrefix;
    }

    /**
     * @return int
     */
    public function getJoinPower(): int
    {
        return $this->joinPower;
    }

    /**
     * @return string
     */
    public function getNameTag(): string
    {
        return $this->nameTag;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return int
     */
    public function getRankName(): int
    {
        return $this->rankName;
    }

    /**
     * @return string
     */
    public function getColor(): string{
        return $this->color;
    }
}