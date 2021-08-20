<?php


namespace baubolp\core\listener\own;


use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\Event;

class TNTLightEvent extends Event implements Cancellable
{
    /** @var Entity */
    private Entity $primedTNT;
    /** @var int  */
    private int $fuse;
    /** @var int  */
    private int $team;
    /** @var int  */
    private int $radius;

    public function __construct(Entity $primedTNT, int $fuse, int $team, int $radius)
    {
        $this->radius = $radius;
        $this->primedTNT = $primedTNT;
        $this->team = $team;
        $this->fuse = $fuse;
    }

    /**
     * @return int
     */
    public function getTeam(): int
    {
        return $this->team;
    }

    /**
     * @return int
     */
    public function getFuse(): int
    {
        return $this->fuse;
    }

    /**
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->primedTNT;
    }

    /**
     * @return int
     */
    public function getRadius(): int
    {
        return $this->radius;
    }
}