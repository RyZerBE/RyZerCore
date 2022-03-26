<?php

declare(strict_types=1);

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Shield;
use ryzerbe\core\listener\entity\EntityDamageByEntityListener;
use ryzerbe\core\player\PMMPPlayer;

class PlayerItemHeldListener implements Listener {
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof PMMPPlayer) return;
        $item = $event->getItem();
		EntityDamageByEntityListener::$delay[$player->getName()] = microtime(true) + 0.15;

        if($item instanceof Shield) {
            //HACK: This fixes a weird bug that players can fly when sneaking and switching to a shield
            $player->setSneaking(!$player->isSneaking());
            $player->setSneaking(!$player->isSneaking());
        }

        if($player->getPvpFishingHook() === null) return;
        $player->getPvpFishingHook()->flagForDespawn();
    }
}