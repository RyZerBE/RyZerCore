<?php

namespace baubolp\core\player;

use baubolp\core\event\PlayerLevelProgressEvent;
use baubolp\core\event\PlayerLevelUpEvent;
use baubolp\core\provider\NetworkLevelProvider;
use baubolp\core\Ryzer;
use Closure;
use pocketmine\utils\TextFormat;
use function ceil;
use function implode;
use function str_repeat;
use function time;

class NetworkLevel {

    /** @var RyzerPlayer  */
    private RyzerPlayer $player;

    /** @var int  */
    private int $level;
    /** @var int  */
    private int $progress;
    /** @var int  */
    private int $progress_today;
    /** @var int  */
    private int $last_progress;

    /**
     * NetworkLevel constructor.
     * @param RyzerPlayer $player
     * @param int $level
     * @param int $progress
     * @param int $progress_today
     * @param int $last_progress
     */
    public function __construct(RyzerPlayer $player, int $level, int $progress, int $progress_today, int $last_progress){
        $this->player = $player;
        $this->level = $level;
        $this->progress = $progress;
        $this->progress_today = $progress_today;
        $this->last_progress = $last_progress;
    }

    /**
     * @return RyzerPlayer
     */
    public function getPlayer(): RyzerPlayer{
        return $this->player;
    }

    /**
     * @return int
     */
    public function getLevel(): int{
        return $this->level;
    }

    /**
     * @return int
     */
    public function getProgress(): int{
        return $this->progress;
    }

    /**
     * @return int
     */
    public function getProgressToday(): int{
        return $this->progress_today;
    }

    /**
     * @param int|null $level
     * @return int
     */
    public function getProgressToLevelUp(?int $level = null): int {
        $level = ($level ?? $this->getLevel());
        if($level <= 1) return 100;
        return ($level * 100 + (10 * $level));
    }

    /**
     * @return int
     */
    public function getProgressPercentage(): int {
        return (100 / $this->getProgressToLevelUp()) * $this->getProgress();
    }

    /**
     * @param int $level
     * @param Closure|null $closure
     */
    public function addLevel(int $level = 1, ?Closure $closure = null): void {
        $this->level += $level;
        NetworkLevelProvider::addLevel($this->getPlayer()->getName(), $level, $closure);

        $this->initLevelUp();
    }

    /**
     * @param int $progress
     * @param Closure|null $closure
     */
    public function addProgress(int $progress, ?Closure $closure = null): void {
        if(ceil($this->last_progress / 86400) !== ceil(time() / 86400)) $this->progress_today = 0;
        $this->progress_today += $progress;
        $this->progress += $progress;
        $this->last_progress = time();

        NetworkLevelProvider::addLevelProgress($this->getPlayer()->getName(), $progress, $this->progress_today, $closure);

        while($this->checkLevelUp()) //Nothing
        (new PlayerLevelProgressEvent($this->getPlayer()->getPlayer(), $progress))->call();
    }

    /**
     * @param int $level
     * @param Closure|null $closure
     */
    public function setLevel(int $level, ?Closure $closure = null): void{
        $this->level = $level;
        NetworkLevelProvider::setLevel($this->getPlayer()->getName(), $level, $closure);
    }

    /**
     * @param int $progress
     * @param Closure|null $closure
     */
    public function setProgress(int $progress, ?Closure $closure = null): void{
        $this->progress = $progress;
        NetworkLevelProvider::setLevelProgress($this->getPlayer()->getName(), $progress, $closure);
    }

    private function checkLevelUp(): bool {
        if($this->getProgress() < $this->getProgressToLevelUp()) return false;
        $this->addLevel();
        return true;
    }

    private function initLevelUp(): void {
        $level = $this->getLevel();
        $this->setProgress(($this->getProgress() - $this->getProgressToLevelUp($level - 1)));
        if($this->getProgress() < 0) $this->setProgress(0);//This should not happen

        $player = $this->getPlayer()->getPlayer();

        (new PlayerLevelUpEvent($player, $level))->call();
        $player->sendMessage(Ryzer::PREFIX.implode("\n".Ryzer::PREFIX,
                [
                    str_repeat(TextFormat::GOLD."✰".TextFormat::YELLOW."❋", 7),
                    TextFormat::BOLD.TextFormat::GOLD."Level Up!",
                    TextFormat::GREEN."You reached level ".TextFormat::GOLD.$level.TextFormat::GREEN."!",
                    TextFormat::GREEN."",
                    str_repeat(TextFormat::GOLD."✰".TextFormat::YELLOW."❋", 7),
                ]
            )
        );
        $player->playSound("random.levelup", 100, 1, [$player]);

        //TODO: Rewards
    }
}