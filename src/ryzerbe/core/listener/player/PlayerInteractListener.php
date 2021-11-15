<?php

namespace ryzerbe\core\listener\player;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use ryzerbe\core\entity\EnderPearl;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\customItem\CustomItemManager;

class PlayerInteractListener implements Listener {

    /**
     * @param PlayerInteractEvent $event
     */
    public function interact(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();
        $action = $event->getAction();
        if(!$player instanceof PMMPPlayer) return;
        
        $customItem = CustomItemManager::getInstance()->getCustomItemByItem($item);
        if($customItem !== null){
            if($customItem->cancelInteract()) $event->setCancelled();
            if($player->hasItemCooldown($item)) return;
            $customItem->onInteract($player, $item);
            return;
        }

        if($item->getId() == ItemIds::ENDER_PEARL && $action === PlayerInteractEvent::RIGHT_CLICK_AIR) {
            $event->setCancelled();
            $player->getInventory()->removeItem(Item::get(ItemIds::ENDER_PEARL));
            $this->createEnderpearl($player);
        }
    }

    /**
     * @param Player $player
     */
    public function createEnderpearl(Player $player){
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