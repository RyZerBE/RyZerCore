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

    const KNOCK_HAND = 0;
    const KNOCK_SWORD = 1;
    const KNOCK_STICK = 2;

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

        if(!isset($this->delay[$attacker->getName()]))
            $this->delay[$attacker->getName()] = microtime(true);

        if($attacker->isCreative()) return;
        if($this->delay[$attacker->getName()] > microtime(true)){
            $event->setCancelled();
            return;
        }

        $event->setModifier(0, EntityDamageEvent::MODIFIER_TOTEM);
        $this->delay[$attacker->getName()] = microtime(true) + 0.45;
    }

    public function BattleMCKnockback(PMMPPlayer $victim, PMMPPlayer $attacker): void{
        $yaw = $attacker->getYaw();
        //var_dump($yaw);
        $look = null;
        if ($yaw >= 315 or $yaw <= 45) {
            // North
            $look = "N";
        } elseif ($yaw >= 45 and $yaw <= 135) {
            // East
            $look = "O";
        } elseif ($yaw >= 135 and $yaw <= 225) {
            // South
            $look = "S";
        } elseif ($yaw >= 225 and $yaw <= 315) {
            // West
            $look = "W";
        } else {
            $look = "N";
        }

        $x = $victim->x - $attacker->x;
        $z = $victim->z - $attacker->z;

        $enchantmentLevel = 1;

        $edit = 0;
        $y_edit = 1;
        $f_edit = 1.355;
        if (abs($x) <= 0 or abs($z) <= 0) {
            if ($attacker->getPitch() < -70 ) {
                $edit = 5;
            }  else {
                $edit = 0.7;
            }
        }

        switch ($look) {
            case "N":
                if ($z < 0.4) {
                    $edit = 3;
                    $enchantmentLevel = 1.2;
                }
                $z += $edit;
                break;
            case "O":
                if ($x > 0.4) {
                    $edit = 3;
                    $enchantmentLevel = 1.2;
                }
                $x -= $edit;
                break;
            case "S":
                if ($z > 0.4) {
                    $edit = 3;
                    $enchantmentLevel = 1.2;
                }
                $z -= $edit;
                break;
            case "W":
                if ($x < 0.4) {
                    $edit = 3;
                    $enchantmentLevel = 1.2;
                }
                $x += $edit;
                break;
        }

        $base = 0.5 * $enchantmentLevel;
        $div = 2 * $enchantmentLevel;
        $y_base = $base * $y_edit - 0.345;

        $f = sqrt($x * $x + $z * $z);
        if($f <= 0){
            return;
        }
        if(mt_rand() / mt_getrandmax() > $victim->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
            $f = $f_edit / $f;

            $motion = clone $victim->getMotion();

            $motion->x /= $div;
            $motion->y /= $div;
            $motion->z /= $div;
            $motion->x += $x * $f * $base;
            $motion->y += $y_base + 0.2;
            $motion->z += $z * $f * $base;

            $victim->setMotion($motion);
        }
    }


    public function RyZerBEKnock(PMMPPlayer $entity, PMMPPlayer $attacker, int $level = 1): void{
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