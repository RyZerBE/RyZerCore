<?php
declare(strict_types=1);
namespace ryzerbe\core\listener\player;


use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use ryzerbe\core\player\PMMPPlayer;


class PlayerSwitchWorldListener implements Listener{
	public function onSwitchLevel(EntityLevelChangeEvent $event){
		$player = $event->getEntity();
		if(!$player instanceof PMMPPlayer) return;

		if($player->getPvpFishingHook() === null) return;
		$player->getPvpFishingHook()->flagForDespawn();
	}
}
