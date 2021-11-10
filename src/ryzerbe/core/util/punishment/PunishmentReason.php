<?php

namespace ryzerbe\core\util\punishment;

use DateInterval;
use DateTime;
use Exception;

class PunishmentReason {

    const BAN = 0;
    const MUTE = 1;

    /** @var string */
    private string $reasonName;
    /** @var int */
    private int $days, $hours;
    /** @var int */
    private int $type;

    /**
     * BanReason constructor.
     *
     * @param string $reasonName
     * @param int $days
     * @param int $hours
     * @param int $type
     */
    public function __construct(string $reasonName, int $days, int $hours, int $type)
    {
        $this->days = $days;
        $this->hours = $hours;
        $this->reasonName = $reasonName;
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getDays(): int
    {
        return $this->days;
    }

    /** @return int
     */
    public function getHours(): int
    {
        return $this->hours;
    }

    /**
     * @return bool
     */
    public function isPermanent(): bool
    {
        return $this->hours <= 0 && $this->days <= 0;
    }

    /**
     * @return string
     */
    public function getReasonName(): string
    {
        return $this->reasonName;
    }

    /**
     * @throws Exception
     * @return DateTime|int
     */
    public function toPunishmentTime(): DateTime|int{
        if($this->isPermanent()) return 0;
        $now = new DateTime();
        if ($this->days > 0)
            $now->add(new DateInterval("P" . $this->days . "D"));


        if ($this->hours > 0)
            $now->add(new DateInterval("PT" . $this->days . "H"));

        return $now;
    }

    /**
     * @return bool
     */
    public function isBan(): bool
    {
        return $this->type === self::BAN;
    }

    /**
     * @return bool
     */
    public function isMute(): bool
    {
        return $this->type === self::MUTE;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }
}