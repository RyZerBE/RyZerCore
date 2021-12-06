<?php

declare(strict_types=1);

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;

class PlayerMoveListener implements Listener {
    private const XZ = [
        [0.3, 0], [0, 0.3], [-0.3, 0], [0, -0.3]
    ];

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $level = $player->getLevel();
        if ($player->fallDistance < 3.2){
            foreach(self::XZ as $xz) {
                if($level->getBlock($player->add($xz[0], 1, $xz[1]))->isSolid()) {
                    $player->resetFallDistance();
                    break;
                }
            }
        }
    }
}