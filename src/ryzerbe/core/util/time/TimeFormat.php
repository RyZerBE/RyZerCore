<?php

namespace ryzerbe\core\util\time;

class TimeFormat {
    private int $years;
    private int $months;
    private int $days;
    private int $hours;
    private int $minutes;
    private int $seconds;

    public function __construct(int $years, int $months, int $days, int $hours, int $minutes, int $seconds){
        $this->years = $years;
        $this->months = $months;
        $this->days = $days;
        $this->hours = $hours;
        $this->minutes = $minutes;
        $this->seconds = $seconds;
    }

    public function getAddTime(): int{
        if($this->getTime() == 0){
            return 0;
        }
        return intval($this->getTime() + time());
    }

    public function getTime(): int{
        return $this->getYears() * 31104000 + $this->getMonths() * 2592000 + $this->getDays() * 86400 + $this->getHours() * 3600 + $this->getMinutes() * 60 + $this->getSeconds();
    }

    public function getYears(): int{
        return $this->years;
    }

    public function getMonths(): int{
        return $this->months;
    }

    public function getDays(): int{
        return $this->days;
    }

    public function getHours(): int{
        return $this->hours;
    }

    public function getMinutes(): int{
        return $this->minutes;
    }

    public function getSeconds(): int{
        return $this->seconds;
    }

    public function asString(): string{
        if($this->getTime() === 0) return "Never (Permanent)";
        return (($this->getYears() !== 0 ? strval($this->getYears()) . " Year" . ($this->getYears() === 1 ? "" : "s") . ", " : "") . ($this->getMonths() !== 0 ? strval($this->getMonths()) . " Month" . ($this->getMonths() === 1 ? "" : "s") . ", " : "") . ($this->getDays() !== 0 ? strval($this->getDays()) . " Day" . ($this->getDays() === 1 ? "" : "s") . ", " : "") . ($this->getHours() !== 0 ? strval($this->getHours()) . " Hour" . ($this->getHours() === 1 ? "" : "s") . ", " : "") . ($this->getMinutes() !== 0 ? strval($this->getMinutes()) . " Minute" . ($this->getMinutes() === 1 ? "" : "s") . ", " : "") . ($this->getSeconds() !== 0 ? strval($this->getSeconds()) . " Second" . ($this->getSeconds() === 1 ? "" : "s") : ""));
    }

    public function asShortString(): string{
        if($this->getTime() === 0) return "???";
        return (($this->getYears() !== 0 ? strval($this->getYears()) . " Y" . ", " : "") . ($this->getMonths() !== 0 ? strval($this->getMonths()) . " M" . ", " : "") . ($this->getDays() !== 0 ? strval($this->getDays()) . " D" . ", " : "") . ($this->getHours() !== 0 ? strval($this->getHours()) . " H" . ", " : "") . (($this->getMinutes() !== 0 && $this->getDays() === 0) ? strval($this->getMinutes()) . " Min" . ", " : "") . (($this->getSeconds() !== 0 && $this->getHours() === 0) ? strval($this->getSeconds()) . " Sec" : ""));
    }
}