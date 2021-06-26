<?php


namespace baubolp\core\listener\own;


use pocketmine\entity\Entity;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\event\Cancellable;
use pocketmine\event\Event;

class TNTLightEvent extends Event implements Cancellable
{
    /** @var \pocketmine\entity\Entity  */
    private $primedTNT;
    /** @var int  */
    private $fuse;
    /** @var int  */
    private $team;
    /** @var int  */
    private $radius;

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
     * @return \pocketmine\entity\Entity
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