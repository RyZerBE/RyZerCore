<?php

namespace ryzerbe\core\player\setting;

class PlayerSettings {
    private bool $moreParticle = false;
    private bool $isToggleRank = false;
    private bool $partyInvites = true;

    public function isMoreParticleActivated(): bool{
        return $this->moreParticle;
    }

    public function setMoreParticle(bool $moreParticle): void{
        $this->moreParticle = $moreParticle;
    }

    public function isRankToggled(): bool{
        return $this->isToggleRank;
    }

    public function setToggleRank(bool $isToggleRank): void{
        $this->isToggleRank = $isToggleRank;
    }

    public function isPartyInvitesEnabled(): bool{
        return $this->partyInvites;
    }

    public function setPartyInvitesEnabled(bool $partyInvites): void{
        $this->partyInvites = $partyInvites;
    }
}