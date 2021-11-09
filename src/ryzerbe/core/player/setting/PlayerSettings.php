<?php

namespace ryzerbe\core\player\setting;

class PlayerSettings {

    /** @var bool  */
    private bool $moreParticle = false;

    /**
     * @return bool
     */
    public function isMoreParticleActivated(): bool{
        return $this->moreParticle;
    }

    /**
     * @param bool $moreParticle
     */
    public function setMoreParticle(bool $moreParticle): void{
        $this->moreParticle = $moreParticle;
    }
}