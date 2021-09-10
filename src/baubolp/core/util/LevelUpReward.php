<?php

namespace baubolp\core\util;

use baubolp\core\player\RyzerPlayer;
use baubolp\core\provider\LanguageProvider;
use Closure;
use pocketmine\utils\TextFormat;

class LevelUpReward {
    /** @var Closure */
    private Closure $closure;
    /** @var int  */
    private int $level;
    /** @var string  */
    private string $name;

    /**
     * @param int $level
     * @param string $name
     * @param Closure $rewardClosure
     */
    public function __construct(int $level, string $name, Closure $rewardClosure){
        $this->level = $level;
        $this->closure = $rewardClosure;
        $this->name = $name;
    }

    /**
     * @param RyzerPlayer $ryzerPlayer
     */
    public function call(RyzerPlayer $ryzerPlayer){
        $closure = $this->closure;
        if($closure === null) return;

        $ryzerPlayer->getPlayer()->sendMessage(TextFormat::DARK_GRAY."[".TextFormat::BLUE."XP".TextFormat::DARK_GRAY."] ".LanguageProvider::getMessageContainer("level-reward-unlocked", $ryzerPlayer->getPlayer()->getName(), ["#reward" => $this->getName()]));
        $closure($this->level, $ryzerPlayer);
    }

    /**
     * @return int
     */
    public function getLevel(): int{
        return $this->level;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }
}