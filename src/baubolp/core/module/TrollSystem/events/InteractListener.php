<?php


namespace baubolp\core\module\TrollSystem\events;


use baubolp\core\Ryzer;
use baubolp\core\module\TrollSystem\forms\TrollMenuForm;
use pocketmine\entity\Entity;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\projectile\Egg;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\utils\TextFormat;

class InteractListener implements Listener
{

    public function interact(PlayerItemHeldEvent $event)
    {
        if(TextFormat::clean($event->getItem()->getCustomName()) == "Troll-Item" && in_array($event->getPlayer()->getName(), Ryzer::getTrollSystem()->trollPlayers)) {
            $event->getPlayer()->sendForm(new TrollMenuForm());
        }
    }

    public function hitBlock(ProjectileHitBlockEvent $event)
    {
        $entity = $event->getEntity();
        if($entity instanceof Egg) {
            $nbt = Entity::createBaseNBT($entity);
            $nbt->setShort("Fuse", 1);
            $tnt = Entity::createEntity(PrimedTNT::NETWORK_ID, $entity->getLevel(), $nbt);
            $tnt->spawnToAll();
        }
    }

    public function dropItem(PlayerDropItemEvent $event)
    {
        if(in_array($event->getPlayer()->getName(), Ryzer::getTrollSystem()->antiDrop)) {
            $event->setCancelled();
        }
    }
}