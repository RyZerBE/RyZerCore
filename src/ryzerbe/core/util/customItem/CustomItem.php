<?php

namespace ryzerbe\core\util\customitem;

use pocketmine\event\Listener;
use pocketmine\item\Item;
use ryzerbe\core\player\PMMPPlayer;

abstract class CustomItem implements Listener {
    /** @var Item */
    protected Item $item;
    /** @var string */
    private string $name = "";
    /** @var string */
    private string $class = "";
    /** @var string */
    private string $uniqueId;
    /** @var int|null */
    private ?int $slot;

    /**
     * CustomItem constructor.
     *
     * @param Item $item
     * @param int|null $slot
     */
    public function __construct(Item $item, ?int $slot = null){
        $this->uniqueId = uniqid();
        $this->slot = $slot;
        $item->getNamedTag()->setString("custom_item", $this->getUniqueId());
        $this->item = $item;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string{
        return $this->uniqueId;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void{
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getClass(): string{
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void{
        $this->class = $class;
    }

    /**
     * @param Item $item
     */
    public function setItem(Item $item): void{
        $item->getNamedTag()->setString("custom_item", $this->getUniqueId());
        $this->item = $item;
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function checkItem(Item $item): bool{
        return CustomItemManager::getInstance()->getCustomItemByItem($item)?->getUniqueId() === $this->getUniqueId();
    }

    /**
     * @param PMMPPlayer $player
     * @param int|null $slot
     */
    public function giveToPlayer(PMMPPlayer $player, ?int $slot = null): void{
        if($slot !== null) {
            $player->getInventory()->setItem($slot, $this->getItem());
            return;
        }
        $slot = $this->getSlot();
        if($slot === null){
            $player->getInventory()->addItem($this->getItem());
        }
        else{
            $player->getInventory()->setItem($slot, $this->getItem());
        }
    }

    /**
     * @return int|null
     */
    public function getSlot(): ?int{
        return $this->slot;
    }

    /**
     * @return Item
     */
    public function getItem(): Item{
        return $this->item;
    }

    /**
     * @param PMMPPlayer $player
     * @param Item $item
     */
    public function onInteract(PMMPPlayer $player, Item $item): void{
    }

    /**
     * @return bool
     */
    public function cancelInteract(): bool{
        return true;
    }
}