<?php


namespace baubolp\core\listener;


use baubolp\core\entity\EnderPearl;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\projectile\Snowball;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class BowHitEntityListener implements Listener
{

    public function arrowHit(ProjectileHitEntityEvent $event)
    {
        $entity = $event->getEntity();
        $shooter = $entity->getOwningEntity();

        if($shooter instanceof Player) { // && !$event->isCancelled()
          if($entity instanceof Arrow || $entity instanceof \baubolp\core\entity\Arrow || $entity instanceof Snowball || $entity instanceof Egg)
            $shooter->playSound('random.levelup', 5, 1.0, [$shooter]);
        }
    }

    public function interact(PlayerInteractEvent $event)
    {
        ////// JAVA ENDERPEARL \\\\\\
        $player = $event->getPlayer();
        if($event->getItem()->getId() == Item::ENDER_PEARL && $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR) {
            $event->setCancelled();
            $player->getInventory()->removeItem(Item::get(Item::ENDER_PEARL));
            $nbt = new CompoundTag("", [
                "Pos" => new ListTag("Pos", [
                    new DoubleTag("", $player->x),
                    new DoubleTag("", $player->y + $player->getEyeHeight()),
                    new DoubleTag("", $player->z)
                ]),
                "Motion" => new ListTag("Motion", [
                    new DoubleTag("", $player->getDirectionVector()->x),
                    new DoubleTag("", $player->getDirectionVector()->y),
                    new DoubleTag("", $player->getDirectionVector()->z)
                ]),
                "Rotation" => new ListTag("Rotation", [
                    new FloatTag("", $player->yaw),
                    new FloatTag("", $player->pitch)
                ]),
            ]);
            $entity = Entity::createEntity(EnderPearl::NETWORK_ID, $player->getLevelNonNull(), $nbt, $player);
            $entity->spawnToAll();
            if ($entity instanceof Projectile)
                $entity->setMotion($entity->getMotion()->multiply(1.7));

            $ev = new ProjectileLaunchEvent($entity);
            $ev->call();
        }
    }

    public function snowBall(ProjectileLaunchEvent $event)
    {
        $entity = $event->getEntity();
        if($entity instanceof Egg || $entity instanceof Snowball) {
            $entity->setMotion($entity->getMotion()->multiply(1.7));
        }
    }

    public function hitBlock(ProjectileHitBlockEvent $event)
    {
        $e = $event->getEntity();
        if($e instanceof Arrow || $e instanceof \baubolp\core\entity\Arrow)
            $e->kill();
    }
}