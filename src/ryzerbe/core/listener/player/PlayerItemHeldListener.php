<?php

declare(strict_types=1);

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\ItemIds;
use pocketmine\item\Shield;
use ryzerbe\core\listener\entity\EntityDamageByEntityListener;
use ryzerbe\core\player\PMMPPlayer;

class PlayerItemHeldListener implements Listener {
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof PMMPPlayer) return;
        $item = $event->getItem();

        if($item->getId() != ItemIds::BOW) {
			$player->lastItemSwitch = microtime(true);
		}
        if(microtime(true) - $player->lastDamage < 0.25 && $event->getItem()->getId() != ItemIds::BOW) {
        	$player->nextHitCancel = true;
		}
        if($item instanceof Shield) {
            //HACK: This fixes a weird bug that players can fly when sneaking and switching to a shield
            $player->setSneaking(!$player->isSneaking());
            $player->setSneaking(!$player->isSneaking());
        }

        if($player->getPvpFishingHook() === null) return;
        $player->getPvpFishingHook()->flagForDespawn();
    }
}