<?php

namespace ryzerbe\core\player\networklevel;

use Closure;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayer;

class LevelUpReward {
    private Closure $closure;
    private int $level;
    private string $name;

    public function __construct(int $level, string $name, Closure $rewardClosure){
        $this->level = $level;
        $this->closure = $rewardClosure;
        $this->name = $name;
    }

    public function call(RyZerPlayer $ryzerPlayer){
        $closure = $this->closure;
        if($closure === null) return;

        $ryzerPlayer->getPlayer()->sendMessage(TextFormat::DARK_GRAY."[".TextFormat::BLUE."XP".TextFormat::DARK_GRAY."] ".LanguageProvider::getMessageContainer("level-reward-unlocked", $ryzerPlayer->getPlayer(), ["#reward" => $this->getName()]));
        $closure($this->level, $ryzerPlayer);
    }

    public function getLevel(): int{
        return $this->level;
    }

    public function getName(): string{
        return $this->name;
    }
}