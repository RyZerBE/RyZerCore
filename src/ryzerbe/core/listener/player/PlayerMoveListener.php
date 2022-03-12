<?php

declare(strict_types=1);

namespace ryzerbe\core\listener\player;

use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class PlayerMoveListener implements Listener {
    private const XZ = [
        [0.3, 0], [0, 0.3], [-0.3, 0], [0, -0.3]
    ];

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $level = $player->getLevel();

        $from = $event->getFrom()->floor();
        $to = $event->getTo()->floor();
        if ($from->getX() != $to->getX() or $from->getY() != $to->getY() or $from->getZ() != $to->getZ()) {
            $block = $player->getLevel()->getBlock($from);
            if ($block->getId() == Block::AIR and $player->getGamemode() != 1) {
                $side = [$from];
                for ($i = 0; $i < 5; $i++)
                    $side[] = $from->getSide($i);
                $player->getLevel()->sendBlocks([$player], $side, UpdateBlockPacket::FLAG_ALL_PRIORITY);
            }
        }

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