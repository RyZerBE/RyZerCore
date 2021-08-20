<?php

namespace baubolp\core\player;

use baubolp\core\provider\NetworkLevelProvider;
use Closure;

class NetworkLevel {

    /** @var RyzerPlayer  */
    private RyzerPlayer $player;

    /** @var int  */
    private int $level;
    /** @var int  */
    private int $progress;

    /**
     * NetworkLevel constructor.
     * @param RyzerPlayer $player
     * @param int $level
     * @param int $progress
     */
    public function __construct(RyzerPlayer $player, int $level, int $progress){
        $this->player = $player;
        $this->level = $level;
        $this->progress = $progress;
    }

    /**
     * @return RyzerPlayer
     */
    public function getPlayer(): RyzerPlayer{
        return $this->player;
    }

    /**
     * @return int
     */
    public function getLevel(): int{
        return $this->level;
    }

    /**
     * @return int
     */
    public function getProgress(): int{
        return $this->progress;
    }

    /**
     * @param int $level
     * @param Closure|null $closure
     */
    public function addLevel(int $level = 1, ?Closure $closure = null): void {
        //Todo: Rewards etc...
        $this->setLevel($this->getLevel() + $level, $closure);
    }

    /**
     * @param int $progress
     * @param Closure|null $closure
     */
    public function addProgress(int $progress, ?Closure $closure = null): void {
        //Todo: Check level up etc...
        $this->setProgress($this->getProgress() + $progress, $closure);
    }

    /**
     * @param int $level
     * @param Closure|null $closure
     */
    public function setLevel(int $level, ?Closure $closure = null): void{
        $this->level = $level;
        NetworkLevelProvider::setLevel($this->getPlayer()->getName(), $level, $closure);
    }

    /**
     * @param int $progress
     * @param Closure|null $closure
     */
    public function setProgress(int $progress, ?Closure $closure = null): void{
        $this->progress = $progress;
        NetworkLevelProvider::setLevelProgress($this->getPlayer()->getName(), $progress, $closure);
    }
}