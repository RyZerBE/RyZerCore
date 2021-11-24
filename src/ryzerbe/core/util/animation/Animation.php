<?php

namespace ryzerbe\core\util\animation;

use function uniqid;

abstract class Animation {
    private int $ticks = 0;
    private string $id;

    public function __construct(){
        $this->id = uniqid();
    }

    public function tick(): void{
        $this->ticks++;
    }

    /**
     * @return int
     */
    public function getCurrentTick(): int{
        return $this->ticks;
    }

    /**
     * @return string
     */
    public function getAnimationId(): string{
        return $this->id;
    }

    public function stop(): void{
        unset(AnimationManager::getInstance()->activeAnimation[$this->getAnimationId()]);
    }

    public function cancel(): void{
        $this->stop();
    }
}