<?php

namespace ryzerbe\core\player\setting;

class PlayerSettings {

    /** @var bool  */
    private bool $moreParticle = false;
    /** @var bool  */
    private bool $isToggleRank = false;
    /** @var bool  */
    private bool $partyInvites = true;

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

    /**
     * @return bool
     */
    public function isRankToggled(): bool{
        return $this->isToggleRank;
    }

    /**
     * @param bool $isToggleRank
     */
    public function setToggleRank(bool $isToggleRank): void{
        $this->isToggleRank = $isToggleRank;
    }

    /**
     * @return bool
     */
    public function isPartyInvitesEnabled(): bool{
        return $this->partyInvites;
    }

    /**
     * @param bool $partyInvites
     */
    public function setPartyInvitesEnabled(bool $partyInvites): void{
        $this->partyInvites = $partyInvites;
    }
}