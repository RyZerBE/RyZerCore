<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\Settings;

class EntityDamageByEntityListener implements Listener {
    private array $delay = [];

    /**
     * @priority HIGH
     */
    public function entityDamage(EntityDamageByEntityEvent $event): void{
        $player = $event->getDamager();
        $entity = $event->getEntity();
        if(!$player instanceof PMMPPlayer){
            $event->setCancelled(false); //NO DELAY
            return;
        }

        $ryzerPlayer = $player->getRyZerPlayer();
        if($ryzerPlayer === null) return;

        if($ryzerPlayer->getPlayerSettings()->isMoreParticleActivated()) {
            $pk = new AnimatePacket();
            $pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
            $pk->entityRuntimeId = $entity->getId();
            $player->getServer()->broadcastPacket([$player], $pk);
        }

        if(!isset($this->delay[$player->getName()]))
            $this->delay[$player->getName()] = microtime(true);

        if($player->isCreative()) return;
        if($this->delay[$player->getName()] > microtime(true)){
            $event->setCancelled();
            return;
        }
        $event->setModifier(0, EntityDamageEvent::MODIFIER_TOTEM);
        $this->delay[$player->getName()] = microtime(true) + 0.5;
        if($entity instanceof PMMPPlayer){
            $item = $player->getInventory()->getItemInHand();
            if($item->hasEnchantment(Enchantment::KNOCKBACK) && !$event->isCancelled()){
                $event->setCancelled();
                $entity->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION);
                $this->knockback($entity, $player, $item->getEnchantmentLevel(Enchantment::KNOCKBACK));
                $entity->setHealth($entity->getHealth() - $event->getFinalDamage());
                //thats here bc I think so its maybe fewer player resistance
            }
        }
    }

    public function knockback(PMMPPlayer $entity, PMMPPlayer $attacker, int $level = 1): void{
        if($level === 0) return;
        $motion = clone $entity->getMotion();
        $motion->y /= 2;
        $motion->y += 0.45;
        if($motion->y > 0.45)
            $motion->y = 0.45;

        if(!$entity->useLadder()){
            $knockBack = 1.1;
            if($level > 1)
                $knockBack = 0.9;
            $motion = new Vector3($attacker->getDirectionVector()->x / $knockBack, $motion->y, $attacker->getDirectionVector()->z / $knockBack);
        }

        if(Settings::$reduce){
            $ownmotion = $attacker->getMotion();
            $ownmotion->setComponents($ownmotion->getX() * 0.6, $ownmotion->getY() * 0.6, $ownmotion->getZ() * 0.6);
            $attacker->setMotion($ownmotion);
            $attacker->setSprinting(false);
        }

        $entity->setMotion($motion);
    }
}