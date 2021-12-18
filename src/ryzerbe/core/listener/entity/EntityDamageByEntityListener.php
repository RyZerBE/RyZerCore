<?php

namespace ryzerbe\core\listener\entity;

use pocketmine\block\BlockIds;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\Settings;
use function microtime;

class EntityDamageByEntityListener implements Listener {
    private array $delay = [];

    const KNOCK_HAND = 0;
    const KNOCK_SWORD = 1;
    const KNOCK_STICK = 2;

    /**
     * @priority HIGH
     */
    public function entityDamage(EntityDamageByEntityEvent $event): void{
        $player = $event->getDamager();
        $entity = $event->getEntity();
        if(!$player instanceof PMMPPlayer) return;

        $ryzerPlayer = $player->getRyZerPlayer();
        if($ryzerPlayer === null) return;

        if($ryzerPlayer->getPlayerSettings()->isMoreParticleActivated()){
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
        if($entity instanceof PMMPPlayer){
            $item = $player->getInventory()->getItemInHand();
            if($event->isCancelled()) return;
            if($player->distance($entity) > 4 && $item->getId() === ItemIds::STICK){
                $event->setCancelled();
                return;
            }
            if($item->hasEnchantment(Enchantment::KNOCKBACK)){
                $this->delay[$player->getName()] = microtime(true) + 0.35;
                $event->setCancelled();
                $entity->setImmobile(true);
                $entity->setImmobile(false);
                $entity->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION);
                $this->knockback($entity, $player, self::KNOCK_STICK);
                $entity->setHealth($entity->getHealth() - $event->getFinalDamage());
                //thats here bc I think so its maybe fewer player resistance
            }elseif($item->getId() != ItemIds::STICK && $item->getId() != ItemIds::GOLDEN_SWORD && $item->getId() != ItemIds::WOODEN_SWORD){
                $this->delay[$player->getName()] = microtime(true) + 0.35;
                $event->setCancelled();
                $entity->setImmobile(true);
                $entity->setImmobile(false);
                $entity->broadcastEntityEvent(ActorEventPacket::HURT_ANIMATION);
                $this->knockback($entity, $player, self::KNOCK_HAND);
                $entity->setHealth($entity->getHealth() - $event->getFinalDamage());
            }
        }
    }

    public function knockback(PMMPPlayer $entity, PMMPPlayer $attacker, int $level = 1): void{
        $motion = clone $entity->getMotion();
        $motion->y /= 2;
        $motion->y += ($level === self::KNOCK_HAND) ? 0.35 : 0.5;
        if($motion->y > 0.45)
            $motion->y = 0.45;

        switch($level){
            case self::KNOCK_HAND:
                $knockBack = 2.5;
                if($attacker->getPitch() >= 70){
                    $motion = new Vector3($attacker->getLookVector(0.48)->x / $knockBack, $motion->y, $attacker->getLookVector(0.48)->z / $knockBack);
                }else{
                    $motion = new Vector3($attacker->getLookVector()->x / $knockBack, $motion->y, $attacker->getLookVector()->z / $knockBack);
                }

                $entity->setMotion($motion);
                break;
            case self::KNOCK_STICK:
                if(!$entity->useLadder()){
                    $knockBack = 1.2;

                    if($attacker->getPitch() >= 70){
                        $motion = new Vector3($attacker->getLookVector(0.48)->x / $knockBack, $motion->y, $attacker->getLookVector(0.48)->z / $knockBack);
                    }else{
                        $motion = new Vector3($attacker->getLookVector()->x / $knockBack, $motion->y, $attacker->getLookVector()->z / $knockBack);
                    }
                }

                if(Settings::$reduce){
                    $ownmotion = $attacker->getMotion();
                    $ownmotion->setComponents($ownmotion->getX() * 0.6, $ownmotion->getY() * 0.6, $ownmotion->getZ() * 0.6);
                    $attacker->setMotion($ownmotion);
                    $attacker->setSprinting(false);
                }

                $entity->setMotion($motion);
                break;
        }
    }
}