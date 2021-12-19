<?php

declare(strict_types=1);

namespace ryzerbe\core\listener\inventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\PlayerOffHandInventory;

class InventoryTransactionListener implements Listener {
    public function onInventoryTransaction(InventoryTransactionEvent $event): void {
        foreach($event->getTransaction()->getInventories() as $inventory) {
            if(!$inventory instanceof PlayerOffHandInventory) continue;
            $event->setCancelled();
            break;
        }
    }
}