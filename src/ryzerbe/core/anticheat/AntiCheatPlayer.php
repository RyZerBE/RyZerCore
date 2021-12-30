<?php

declare(strict_types=1);

namespace ryzerbe\core\anticheat;

use Exception;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\anticheat\entity\KillAuraBot;
use ryzerbe\core\player\PMMPPlayer;
use function array_filter;
use function array_shift;
use function array_sum;
use function count;
use function floatval;
use function microtime;
use function round;
use function strval;
use function time;
use const PHP_INT_MAX;

class AntiCheatPlayer {
    private Player $player;

    public const CLICKS_OFFSET_LESS_30 = 5;
    public const CLICKS_OFFSET_LESS_50 = 10;
    public const CLICKS_OFFSET_LESS_100 = 15;
    public const CLICKS_OFFSET_DEFAULT = 20;

    public const MIN_CLICKS = 15;

    protected int $clicks = 0;
    protected int $clicksPerSecond = 0;

    protected array $consistentClicks = [];

    protected array $warnings = [];
    public array $hitEntityCount = [];

    public float|int $breakTime = -1;
    private float|int $lastJump;
    private float|int $lastBlockPlace;
    public float|int|null $lastHitCheck = null;

    public float|int $breakCount = 0;

    private int $moveOnAirCount = 0;
    private int $airJumpCount = 0;
    private int $killAuraCount = 0;
    private int $speedCount = 0;
    private float $serverMotion = 0.0;
    private float|int $maxFlightHeight = 0.0;

    /** @var int  */
    private int $tickOnAir = 0;
    /** @var array  */
    private array $fallDistanceLog = [];

    public Vector3 $lastVector;
    public ?KillAuraBot $killAuraBot = null;

    public string $lastFlagReason = "BroxstarIstFett";

    public function __construct(Player $player){
        $this->player = $player;
        $this->lastVector = $player->asVector3();
        $this->lastJump = microtime(true);
        $this->lastBlockPlace = microtime(true);
    }

    /**
     * @return PMMPPlayer
     */
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
        $currentTime = time();

        $consistentClicks = [];
        foreach($this->consistentClicks as $time => $clicks) {
            if(($currentTime - $time) <= $seconds) {
                $consistentClicks[$time] = $clicks;
            }
        }
        if(count($consistentClicks) <= 0) return 0;
        return (int)round(array_sum($consistentClicks) / count($consistentClicks));
    }

    public function hasConsistentClicks(int $seconds = 5): bool {
        if(count($this->consistentClicks) <= 0) return false;
        $lastClicks = -1;
        $currentTime = time();
        $cps = $this->getClicksPerSecond();
        foreach($this->consistentClicks as $time => $clicks) {
            if(($currentTime - $time) > $seconds) continue;
            if($lastClicks === -1 || abs($clicks - $lastClicks) <= match (true) {
                    ($cps <= 30) => self::CLICKS_OFFSET_LESS_30,
                    ($cps <= 50) => self::CLICKS_OFFSET_LESS_50,
                    ($cps <= 100) => self::CLICKS_OFFSET_LESS_100,
                    default => self::CLICKS_OFFSET_DEFAULT
                }) {
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
        if($this->getClicks() > 120) {
            $this->getPlayer()->kickFromProxy(TextFormat::RED."Too many batch packets!");
            return;
        }
        $this->clicks++;
    }

    public function resetClicks(): void {
        $this->clicksPerSecond = 0;
        $this->clicks = 0;
        $this->consistentClicks = [];
    }

    public function getWarnings(Check $check, int $seconds = 60): int{
        $currentTime = time();
        return count(array_filter($this->warnings[$check::class] ?? [], function(int $time) use ($seconds, $currentTime): bool {
            return $time > ($currentTime - $seconds);
        }));
    }

    public function addWarning(Check $check): void{
        $this->warnings[$check::class][] = time();
        if(count($this->warnings[$check::class]) > 500){
            array_shift($this->warnings[$check::class]);
        }
        $warnings = $this->getWarnings($check, 30);
        $ban = $warnings >= $check->getMaxWarnings();

        if(!$this->getPlayer()->hasDelay($check::class."_once")){
            $check->sendWarningMessage($this->getPlayer(), $ban);
            $this->getPlayer()->addDelay($check::class."_once", 1);
        }
        if(
            ($warnings >= $check->getMinWarningsPerReport() &&
                !$this->getPlayer()->hasDelay($check::class)) || $ban
        ){
            $this->getPlayer()->addDelay($check::class, 10);
            $check->sendWarningMessage($this->getPlayer(), $ban);
            #PunishmentProvider::punishPlayer($this->getPlayer()->getName(), "AntiCheat", 15);
        }
    }


    /**
     * @return bool
     */
    public function isServerMotionSet(): bool
    {
        return (microtime(true) - $this->serverMotion < 3);
    }

    public function setServerMotionSet(): void
    {
        $this->maxFlightHeight = PHP_INT_MAX;
        if ($this->getPlayer()->fallDistance == 0) $this->getPlayer()->fallDistance = 0.1;
        $this->serverMotion = microtime(true);
    }

    /**
     * @return float|int
     */
    public function getBreakCount(): float|int{
        return $this->breakCount;
    }

    /**
     * @return float|int
     */
    public function getBreakTime(): float|int{
        return $this->breakTime;
    }

    /**
     * @return float|int
     */
    public function getMaxFlightHeight(): float|int{
        return $this->maxFlightHeight;
    }

    /**
     * @param float|int $maxFlightHeight
     */
    public function setMaxFlightHeight(float|int $maxFlightHeight): void{
        $this->maxFlightHeight = $maxFlightHeight;
    }

    /**
     * @return int
     */
    public function getMoveOnAirCount(): int{
        return $this->moveOnAirCount;
    }

    public function countMoveOnAir(){
        $this->moveOnAirCount++;
    }

    public function countAirJump(){
        $this->airJumpCount++;
    }

    public function resetCountsOnAir(): void{
        $this->moveOnAirCount = 0;
    }

    public function resetAirJumpCount(): void{
        $this->airJumpCount = 0;
    }

    /**
     * @return float
     */
    public function getServerMotion(): float{
        return $this->serverMotion;
    }

    public function resetMaxFlightHeight(){
        $this->maxFlightHeight = 0.0;
    }

    public function countKillAura(): void{
        $this->killAuraCount++;
    }

    public function resetKillAuraCount(){
        $this->killAuraCount = 0;
    }

    /**
     * @return int
     */
    public function getKillAuraCount(): int{
        return $this->killAuraCount;
    }

    public function flag(string $reason, Check $check): void{
        $this->getPlayer()->teleport($this->lastVector);
        $this->lastFlagReason = $reason;
        $this->addWarning($check);
    }

    public function jump(): void{
        $this->lastJump = microtime(true);
    }

    public function placeBlock(): void{
        $this->lastBlockPlace = microtime(true);
    }

    /**
     * @return float|int
     */
    public function getLastBlockPlaceTime(): float|int{
        return $this->lastBlockPlace;
    }

    /**
     * @return string
     */
    public function getLastFlagReason(): string{
        return $this->lastFlagReason;
    }

    /**
     * @return float|int
     */
    public function getLastJump(): float|int{
        return $this->lastJump;
    }

    /**
     * @return int
     */
    public function getAirJumpCount(): int{
        return $this->airJumpCount;
    }

    public function resetHitCount(): void{
        $this->hitEntityCount = [];
    }

    public function resetLastHitCheck(): void{
        $this->lastHitCheck = null;
    }

    public function airTick(): void{
        $this->tickOnAir++;
    }

    public function resetAirTick(): void{
        $this->tickOnAir = 0;
    }

    public function logDistance(float $distance): void{
        $this->fallDistanceLog[strval(microtime(true))] = $distance;
    }

    public function resetFallDistanceLog(): void{
        $this->fallDistanceLog = [];
    }

    /**
     * @return int
     */
    public function getTickOnAir(): int{
        return $this->tickOnAir;
    }

    /**
     * @param int $sec
     * @return array
     */
    public function getFallDistanceLog(int $sec = 0): array{
        if($sec === 0) return $this->fallDistanceLog;
        if($sec >= 5){
            Server::getInstance()->getLogger()->error("AntiCheatPlayer#getFalDistanceLog => Sec/s cannot greater than 4!");
            return [];
        }

        $log = [];
        foreach($this->fallDistanceLog as $microTime => $fallDistance) {
            if((microtime(true) - (float)$microTime) < $sec) $log[] = $fallDistance;
        }

        return $log;
    }

    /**
     * @return Vector3
     */
    public function getLastVector3(): Vector3{
        return $this->lastVector;
    }

    public function countSpeed(): void{
        $this->speedCount++;
    }

    public function resetSpeedCount(): void{
        $this->speedCount = 0;
    }

    /**
     * @return int
     */
    public function getSpeedCount(): int{
        return $this->speedCount;
    }
}