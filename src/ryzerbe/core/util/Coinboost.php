<?php

namespace ryzerbe\core\util;

use DateTime;
use ryzerbe\core\player\PMMPPlayer;
use function round;

class Coinboost {

    private int $percent;
    private DateTime $endTime;
    private PMMPPlayer $player;
    private bool $forAll;

    /**
     * @param PMMPPlayer $player
     * @param int $percent
     * @param DateTime $endTime
     * @param bool $forAll
     */
    public function __construct(PMMPPlayer $player, int $percent, DateTime $endTime, bool $forAll){
        $this->percent = $percent;
        $this->endTime = $endTime;
        $this->player = $player;
        $this->forAll = $forAll;
    }

    /**
     * @return bool
     */
    public function isForAll(): bool{
        return $this->forAll;
    }

    /**
     * @return PMMPPlayer
     */
    public function getPlayer(): PMMPPlayer{
        return $this->player;
    }

    /**
     * @return int
     */
    public function getPercent(): int{
        return $this->percent;
    }

    /**
     * @return DateTime
     */
    public function getEndTime(): DateTime{
        return $this->endTime;
    }

    public function isValid(): bool{
        return (new DateTime()) < $this->endTime;
    }

    /**
     * @param PMMPPlayer $player
     * @param int $gaveCoins
     */
    public function boostCoins(PMMPPlayer $player, int $gaveCoins){
        $rbePlayer = $player->getRyZerPlayer();
        if($rbePlayer === null) return;


        $rbePlayer->addCoins($gaveCoins + round((($this->percent * $gaveCoins) / 100)), true);
        $rbePlayer->sendTranslate("player-coinboost-get", ["#booster" => (($this->getPlayer()->getRyZerPlayer() === null) ? "§f" : $this->getPlayer()->getRyZerPlayer()->getRank()->getColor()).$this->getPlayer()->getName(), "#percent" => $this->percent]);
    }
}