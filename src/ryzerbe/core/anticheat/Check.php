<?php

declare(strict_types=1);

namespace ryzerbe\core\anticheat;

use pocketmine\event\Listener;
use pocketmine\Player;

abstract class Check implements Listener {
    abstract public function sendWarningMessage(Player $player): void;

    abstract public function getMinWarningsPerReport(): int;
    abstract public function getMaxWarnings(): int;
    abstract public function getImportance(AntiCheatPlayer $antiCheatPlayer): string;

    public function onUpdate(int $currentTick): bool {
        return false;
    }

    public function scheduleUpdate(): void {
        AntiCheatManager::scheduleCheckUpdate($this);
    }
}