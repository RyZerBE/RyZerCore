<?php

namespace ryzerbe\core\util\customitem;

use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use ReflectionException;
use ryzerbe\core\RyZerBE;

class CustomItemManager {
    use SingletonTrait;
    /** @var array */
    private array $customItems = [];

    /**
     * @param string $name
     * @return CustomItem|null
     */
    public function getCustomItemByName(string $name): ?CustomItem{
        foreach($this->getCustomItems() as $customItem){
            if($customItem->getName() === $name) return $customItem;
        }
        return null;
    }

    /**
     * @return CustomItem[]
     */
    public function getCustomItems(): array{
        return $this->customItems;
    }

    /**
     * @param string $class
     * @return CustomItem|null
     */
    public function getCustomItemByClass(string $class): ?CustomItem{
        foreach($this->getCustomItems() as $customItem){
            if($customItem->getClass() === $class) return $customItem;
        }
        return null;
    }

    /**
     * @param Item $item
     * @return CustomItem|null
     */
    public function getCustomItemByItem(Item $item): ?CustomItem{
        $customItemUniqueId = $item->getNamedTag()->getString("custom_item", "N/A");
        if($customItemUniqueId === "N/A") return null;
        $customItem = CustomItemManager::getInstance()->getCustomItem($customItemUniqueId);
        if($customItem === null) return null;
        return (in_array($customItemUniqueId, array_map(function(CustomItem $item): string{
            return $item->getUniqueId();
        }, CustomItemManager::getInstance()->getCustomItems())) ? $customItem : null);
    }

    /**
     * @param string $uniqueId
     * @return CustomItem|null
     */
    public function getCustomItem(string $uniqueId): ?CustomItem{
        return $this->customItems[$uniqueId] ?? null;
    }

    /**
     * @param CustomItem[] $customItems
     * @throws ReflectionException
     */
    public function registerAll(array $customItems): void{
        foreach($customItems as $item){
            $this->registerCustomItem($item);
        }
    }

    /**
     * @param CustomItem $item
     * @throws ReflectionException
     */
    public function registerCustomItem(CustomItem $item): void{
        $reflection = new ReflectionClass($item::class);
        $item->setName($reflection->getShortName());
        $item->setClass($item::class);
        $this->customItems[$item->getUniqueId()] = $item;
        Server::getInstance()->getPluginManager()->registerEvents($item, RyZerBE::getPlugin());
    }
}