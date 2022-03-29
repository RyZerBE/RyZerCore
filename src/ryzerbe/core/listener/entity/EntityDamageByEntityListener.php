<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\block\BlockIds;
use pocketmine\entity\Attribute;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use ryzerbe\core\entity\Arrow;
use ryzerbe\core\item\rod\entity\FishingHook;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\Settings;
use function microtime;

class EntityDamageByEntityListener implements Listener {

    /**
     * @priority HIGH
     */
    public function entityDamage(EntityDamageByEntityEvent $event): void{
        $attacker = $event->getDamager();
        $entity = $event->getEntity();
        if(!$attacker instanceof PMMPPlayer) return;

        $ryzerPlayer = $attacker->getRyZerPlayer();
        if($ryzerPlayer === null) return;

		if($event instanceof EntityDamageByChildEntityEvent) {
			$child = $event->getChild();
			$entity = $event->getEntity();
			if ($entity instanceof PMMPPlayer) {
				$player = $child->getOwningEntity();
				if ($player instanceof PMMPPlayer) {
					if ($child instanceof Arrow || $child instanceof FishingHook || $child instanceof Snowball || $child instanceof Egg) {
						$player->nextHitCancel = false;
						$player->lastDamage = 0;
					}
				}
			}
		}

        if($ryzerPlayer->getPlayerSettings()->isMoreParticleActivated()){
            $pk = new AnimatePacket();
            $pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
            $pk->entityRuntimeId = $entity->getId();
            $attacker->getServer()->broadcastPacket([$attacker], $pk);
        }

        if($attacker->isCreative()) return;

        if($attacker->nextHitCancel) {
        	$attacker->nextHitCancel = false;
        	$event->setCancelled();
		}
        if((microtime(true) - $attacker->lastItemSwitch) < 0.25 && (microtime(true) - $attacker->lastDamage) < 0.25) {
        	$event->setCancelled();
		}
        $attacker->lastDamage = microtime(true);
        $event->setModifier(0, EntityDamageEvent::MODIFIER_TOTEM);
    }
}