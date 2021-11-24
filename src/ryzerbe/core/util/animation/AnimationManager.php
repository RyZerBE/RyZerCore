<?php

namespace ryzerbe\core\util\animation;

use pocketmine\utils\SingletonTrait;

class AnimationManager {
    use SingletonTrait;
    /** @var Animation[]  */
    public array $activeAnimation = [];

    public function addActiveAnimation(Animation $animation){
        $this->activeAnimation[$animation->getAnimationId()] = $animation;
    }

    public function getActiveAnimations(): array{
        return $this->activeAnimation;
    }
}