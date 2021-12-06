<?php

declare(strict_types=1);

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;

class PlayerMoveListener implements Listener {
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $level = $player->getLevel();
        if (
            $player->fallDistance < 3.2
            && (
                $level->getBlock($player->subtract($player->width / 2, 1, $player->width / 2))->isSolid() ||
                $level->getBlock($player->add($player->width / 2, -1, $player->width / 2))->isSolid()
            )
        ) $player->resetFallDistance();
    }
}