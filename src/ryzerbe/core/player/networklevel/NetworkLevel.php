<?php

namespace ryzerbe\core\player\networklevel;

use Closure;
use pocketmine\utils\TextFormat;
use ryzerbe\core\event\player\networklevel\PlayerLevelProgressEvent;
use ryzerbe\core\event\player\networklevel\PlayerLevelUpEvent;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\RyZerBE;

class NetworkLevel {
    private RyZerPlayer $player;

    private int $level;
    private int $progress;
    private int $progress_today;
    private int $last_progress;

    public function __construct(RyZerPlayer $player, int $level, int $progress, int $progress_today, int $last_progress){
        $this->player = $player;
        $this->level = $level;
        $this->progress = $progress;
        $this->progress_today = $progress_today;
        $this->last_progress = $last_progress;
    }

    public function getPlayer(): RyZerPlayer{
        return $this->player;
    }

    public function getLevel(): int{
        return $this->level;
    }

    public function getProgress(): int{
        return $this->progress;
    }

    public function getProgressToday(): int{
        return $this->progress_today;
    }

    public function getProgressToLevelUp(?int $level = null): int {
        $level = ($level ?? $this->getLevel());
        return match (true) {
            ($level < 2) => 500,
            ($level <= 10) => 1000,
            ($level <= 25) => 2000,
            ($level <= 50) => 5000,
            ($level <= 75) => 7500,
            ($level <= 100) => 10000,
            default => 15000
        };
    }

    public function getLevelColor(int $level = null): string{
        $level = ($level ?? $this->getLevel());
        return match (true) {
            ($level <= 5) => TextFormat::DARK_GRAY,
            ($level <= 10) => TextFormat::GRAY,
            ($level <= 25) => TextFormat::BLUE,
            ($level <= 50) => TextFormat::AQUA,
            ($level <= 75) => TextFormat::LIGHT_PURPLE,
            default => TextFormat::DARK_RED
        };
    }

    public function getProgressPercentage(): int {
        return (100 / $this->getProgressToLevelUp()) * $this->getProgress();
    }

    public function addLevel(int $level = 1, ?Closure $closure = null): void {
        $this->level += $level;
        NetworkLevelProvider::addLevel($this->getPlayer()->getPlayer()->getName(), $level, $closure);

        $this->initLevelUp();
    }

    public function getMultiplier(): float|int{
        $progress_today = $this->getProgressToday();
        return match (true) {
            ($progress_today >= 10000) => 0.03,
            ($progress_today >= 5000) => 0.1,
            default => 1
        };
    }

    public function addProgress(int $progress, ?Closure $closure = null): void {
        if(ceil($this->last_progress / 86400) !== ceil(time() / 86400)) $this->progress_today = 0;
        $multiplier = $this->getMultiplier();

        $progress = (int)ceil($progress * $multiplier);

        $this->progress_today += $progress;
        $this->progress += $progress;
        $this->last_progress = time();

        NetworkLevelProvider::addLevelProgress($this->getPlayer()->getPlayer()->getName(), $progress, $this->progress_today, $closure);
        $this->getPlayer()->getPlayer()->sendMessage(TextFormat::DARK_GRAY."[".TextFormat::BLUE."XP".TextFormat::DARK_GRAY."] ".TextFormat::GREEN."+ $progress XP");
        while($this->checkLevelUp()){
            //TODO: This should be fixed. Otherwise Matze will kill the server again
        }
        (new PlayerLevelProgressEvent($this->getPlayer()->getPlayer(), $progress))->call();
    }

    public function addXP(int $xp, ?Closure $closure){
        $this->addProgress($xp, $closure);
    }

    public function setLevel(int $level, ?Closure $closure = null): void{
        $this->level = $level;
        NetworkLevelProvider::setLevel($this->getPlayer()->getPlayer()->getName(), $level, $closure);
    }

    public function setProgress(int $progress, ?Closure $closure = null): void{
        $this->progress = $progress;
        NetworkLevelProvider::setLevelProgress($this->getPlayer()->getPlayer()->getName(), $progress, $closure);
    }

    private function checkLevelUp(): bool {
        if($this->getProgress() < $this->getProgressToLevelUp()) return false;
        $this->addLevel();
        return true;
    }

    private function initLevelUp(): void{
        $level = $this->getLevel();
        $this->setProgress(($this->getProgress() - $this->getProgressToLevelUp($level - 1)));
        if($this->getProgress() < 0) $this->setProgress(0);//This should not happen

        $player = $this->getPlayer()->getPlayer();

        (new PlayerLevelUpEvent($player, $level))->call();
        $player->sendMessage(str_repeat(TextFormat::GOLD."✰".TextFormat::YELLOW."❋", 7));
        $player->sendMessage(implode("\n",
                [
                    TextFormat::GREEN."",
                    RyZerBE::PREFIX.TextFormat::BOLD.TextFormat::GOLD."Level Up!",
                    RyZerBE::PREFIX.TextFormat::GREEN."You reached level ".TextFormat::GOLD.$level.TextFormat::GREEN."!",
                    TextFormat::GREEN."",
                ]
            )
        );
        $player->sendMessage(str_repeat(TextFormat::GOLD."✰".TextFormat::YELLOW."❋", 7));
        $player->playSound("random.levelup", 100, 1, [$player]);

        $reward = NetworkLevelProvider::getReward($level);
        if($reward === null) return;

        $reward->call($this->getPlayer());
    }
}