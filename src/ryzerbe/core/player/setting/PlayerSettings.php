<?php

namespace ryzerbe\core\player\setting;

class PlayerSettings {
    private bool $moreParticle = false;
    private bool $isToggleRank = false;
    private bool $partyInvites = true;
    private bool $friendRequests = true;
    private bool $msg_toggle = true;

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

    /**
     * @return bool
     */
    public function isFriendRequestsEnabled(): bool{
        return $this->friendRequests;
    }

    /**
     * @param bool $friendRequests
     */
    public function setFriendRequestsEnabled(bool $friendRequests): void{
        $this->friendRequests = $friendRequests;
    }

    /**
     * @return bool
     */
    public function isMsgEnabled(): bool{
        return $this->msg_toggle;
    }

    /**
     * @param bool $msg_toggle
     */
    public function setMsgEnabled(bool $msg_toggle): void{
        $this->msg_toggle = $msg_toggle;
    }
}