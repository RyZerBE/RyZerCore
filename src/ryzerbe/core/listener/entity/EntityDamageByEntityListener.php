<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\block\BlockIds;
use pocketmine\entity\Attribute;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use ryzerbe\core\entity\Arrow;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\Settings;
use function microtime;

class EntityDamageByEntityListener implements Listener {
    private array $delay = [];

    /**
     * @priority HIGH
     */
    public function entityDamage(EntityDamageByEntityEvent $event): void{
        $attacker = $event->getDamager();
        $entity = $event->getEntity();
        if(!$attacker instanceof PMMPPlayer) return;
        $ryzerPlayer = $attacker->getRyZerPlayer();
        if($ryzerPlayer === null) return;

        if($ryzerPlayer->getPlayerSettings()->isMoreParticleActivated()){
            $pk = new AnimatePacket();
            $pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
            $pk->entityRuntimeId = $entity->getId();
            $attacker->getServer()->broadcastPacket([$attacker], $pk);
        }

        if($attacker->isCreative()) return;
        if(isset($this->delay[$attacker->getName()]) and $this->delay[$attacker->getName()] > microtime(true)){
            $event->setCancelled();
            return;
        }
        $event->setModifier(0, EntityDamageEvent::MODIFIER_TOTEM);
        $this->delay[$attacker->getName()] = microtime(true) + 0.1;
    }
}