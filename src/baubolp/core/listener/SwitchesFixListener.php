<?php


namespace baubolp\core\listener;


use baubolp\core\player\CorePlayer;
use baubolp\core\Ryzer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;

class SwitchesFixListener implements Listener
{
    /** @var array     */
    private array $delay = [];

    /**
     * @param EntityDamageByEntityEvent $event
     * @priority HIGH
     */
    public function entityDamage(EntityDamageByEntityEvent $event){
        $player = $event->getDamager();
        $entity = $event->getEntity();
        if(!$player instanceof Player){
            $event->setCancelled(false); //NO DELAY
            return;
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
        if($player instanceof CorePlayer && $entity instanceof CorePlayer){
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

    public function knockback(CorePlayer $entity, CorePlayer $attacker, int $level = 1){
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

        if(Ryzer::isReduce()){
            $ownmotion = $attacker->getMotion();
            $ownmotion->setComponents($ownmotion->getX() * 0.6, $ownmotion->getY() * 0.6, $ownmotion->getZ() * 0.6);
            $attacker->setMotion($ownmotion);
            $attacker->setSprinting(false);
        }

        $entity->setMotion($motion);
    }
}