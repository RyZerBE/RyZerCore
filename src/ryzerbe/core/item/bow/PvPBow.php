<?php

namespace ryzerbe\core\item\bow;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow as ArrowEntity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Tool;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use ryzerbe\core\entity\Arrow;
use ryzerbe\core\player\PMMPPlayer;
use function intdiv;
use function min;

class PvPBow extends Tool {

    public function __construct(int $meta = 0){
        parent::__construct(self::BOW, $meta, "Bow");
    }

    public function getFuelTime(): int{
        return 200;
    }

    public function getMaxDurability(): int{
        return 385;
    }

    public function onClickAir(Player $player, Vector3 $directionVector): bool{
        $player->setUsingItem(true);
        return true;
    }

    /**
     * @param PMMPPlayer $player
     * @return bool
     */
    public function onReleaseUsing(Player $player): bool{
        if($player->isSurvival() and !$player->getInventory()->contains(ItemFactory::get(Item::ARROW, 0, 1))){
            $player->getInventory()->sendContents($player);
            if($player->isOp()) $player->sendMessage("You need an arrow to shoot!");
            return false;
        }

        $eyePos = $player->getEyePos();
        $nbt = Entity::createBaseNBT(
            $eyePos,
            $player->getDirectionVector(),
            ($player->yaw > 180 ? 360 : 0) - $player->yaw,
            -$player->pitch
        );

        $diff = $player->getItemUseDuration();
        $p = $diff / 20;
        $baseForce = min((($p ** 2) + $p * 2) / 3, 1);

        $entity = new Arrow($player->getLevelNonNull(), $nbt, $player, $baseForce > 0);
        $infinity = $this->hasEnchantment(Enchantment::INFINITY);
        if($infinity){
            $entity->setPickupMode(ArrowEntity::PICKUP_CREATIVE);
        }
        if(($punchLevel = $this->getEnchantmentLevel(Enchantment::PUNCH)) > 0){
            $entity->setPunchKnockback($punchLevel);
        }
        if(($powerLevel = $this->getEnchantmentLevel(Enchantment::POWER)) > 0){
            $entity->setBaseDamage($entity->getBaseDamage() + (($powerLevel + 1) / 2));
        }
        if($this->hasEnchantment(Enchantment::FLAME)){
            $entity->setOnFire(intdiv($entity->getFireTicks(), 20) + 100);
        }
        $ev = new EntityShootBowEvent($player, $this, $entity, $baseForce * 3);

        if($player->isSpectator()){
            $ev->setCancelled();
        }

        $ev->call();

        $entity = $ev->getProjectile(); //This might have been changed by plugins

        if($ev->isCancelled()){
            $entity->flagForDespawn();
            $player->getInventory()->sendContents($player);
        }else{
            $entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
            if($player->isSurvival()){
                if(!$infinity){
                    $player->getInventory()->removeItem(ItemFactory::get(ItemIds::ARROW, 0, 1));
                }
                $this->applyDamage(1);
            }

            if($entity instanceof Projectile){
                $projectileEv = new ProjectileLaunchEvent($entity);
                $projectileEv->call();
                if($projectileEv->isCancelled()){
                    $ev->getProjectile()->flagForDespawn();
                }else{
                    $ev->getProjectile()->spawnToAll();
                    $player->getLevelNonNull()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_BOW);
                }
            }else{
                $entity->spawnToAll();
            }
        }

        return true;
    }
}