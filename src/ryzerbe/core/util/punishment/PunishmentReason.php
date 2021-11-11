<?php

namespace ryzerbe\core\util\punishment;

use DateInterval;
use DateTime;
use Exception;

class PunishmentReason {
    public const BAN = 0;
    public const MUTE = 1;

    private string $reasonName;
    private int $days;
    private int $hours;
    private int $type;

    public function __construct(string $reasonName, int $days, int $hours, int $type){
        $this->days = $days;
        $this->hours = $hours;
        $this->reasonName = $reasonName;
        $this->type = $type;
    }

    public function getDays(): int{
        return $this->days;
    }

    public function getHours(): int{
        return $this->hours;
    }

    public function getReasonName(): string{
        return $this->reasonName;
    }

    /**
     * @throws Exception
     */
    public function toPunishmentTime(): DateTime|int{
        if($this->isPermanent()) return 0;
        $now = new DateTime();
        if($this->days > 0){
            $now->add(new DateInterval("P" . $this->days . "D"));
        }
        if($this->hours > 0){
            $now->add(new DateInterval("PT" . $this->days . "H"));
        }
        return $now;
    }

    public function isPermanent(): bool{
        return $this->hours <= 0 && $this->days <= 0;
    }

    public function isBan(): bool{
        return $this->type === self::BAN;
    }

    public function isMute(): bool{
        return $this->type === self::MUTE;
    }

    public function getType(): int{
        return $this->type;
    }
}