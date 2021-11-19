<?php

declare(strict_types=1);

namespace ryzerbe\core\anticheat;

use pocketmine\Player;
use function array_shift;
use function array_sum;
use function count;
use function round;
use function time;

class AntiCheatPlayer {
    private Player $player;

    public const CLICKS_OFFSET = 3;
    public const MIN_CLICKS = 18;

    protected int $clicks = 0;
    protected int $clicksPerSecond = 0;

    protected array $consistentClicks = [];

    protected array $warnings = [];

    public function __construct(Player $player){
        $this->player = $player;
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getClicksPerSecond(): float{
        return $this->clicksPerSecond;
    }

    public function setClicksPerSecond(int $clicksPerSecond): void{
        $this->clicksPerSecond = $clicksPerSecond;
        $this->consistentClicks[time()] = $clicksPerSecond;

        if(count($this->consistentClicks) > 150) array_shift($this->consistentClicks);
    }

    public function getConsistentClicks(int $seconds = 1): int{
        if(count($this->consistentClicks) <= 0) return 0;
        $currentTime = time();

        $consistentClicks = [];
        foreach($this->consistentClicks as $time => $clicks) {
            if(($currentTime - $time) <= $seconds) {
                $consistentClicks[$time] = $clicks;
            }
        }
        return (int)round(array_sum($consistentClicks) / count($consistentClicks));
    }

    public function hasConsistentClicks(int $seconds = 5): bool {
        if(count($this->consistentClicks) <= 0) return false;
        $lastClicks = -1;
        $currentTime = time();
        foreach($this->consistentClicks as $time => $clicks) {
            if(($currentTime - $time) > $seconds) continue;
            if($lastClicks === -1 || abs($clicks - $lastClicks) <= self::CLICKS_OFFSET) {
                $lastClicks = $clicks;
                continue;
            }
            return false;
        }
        return $lastClicks > self::MIN_CLICKS;
    }

    public function setClicks(int $clicks): void{
        $this->clicks = $clicks;
    }

    public function getClicks(): int{
        return $this->clicks;
    }

    public function addClick(): void {
        $this->clicks++;
    }

    public function resetClicks(): void {
        $this->clicksPerSecond = 0;
        $this->clicks = 0;
        $this->consistentClicks = [];
    }

    public function getWarnings(Check $check): int{
        return $this->warnings[$check::class] ?? 0;
    }

    public function addWarning(Check $check): void {
        $this->warnings[$check::class] = ($this->warnings[$check::class] ?? 0) + 1;
        $check->sendWarningMessage($this->getPlayer());
    }
}