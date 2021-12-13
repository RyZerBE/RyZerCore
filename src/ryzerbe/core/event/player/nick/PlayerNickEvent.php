<?php

namespace ryzerbe\core\event\player\nick;

use pocketmine\entity\Skin;
use pocketmine\event\player\PlayerEvent;
use ryzerbe\core\player\RyZerPlayer;

class PlayerNickEvent extends PlayerEvent {

    private string $nickName;
    private Skin $nickSkin;

    private RyZerPlayer $ryZerPlayer;

    public function __construct(RyZerPlayer $player, string $nickName, Skin $nickSkin){
        $this->player = $player->getPlayer();
        $this->ryZerPlayer = $player;
        $this->nickName = $nickName;
        $this->nickSkin = $nickSkin;
    }

    /**
     * @return RyZerPlayer
     */
    public function getRyZerPlayer(): RyZerPlayer{
        return $this->ryZerPlayer;
    }

    /**
     * @return string
     */
    public function getNickName(): string{
        return $this->nickName;
    }

    /**
     * @return Skin
     */
    public function getNickSkin(): Skin{
        return $this->nickSkin;
    }
}