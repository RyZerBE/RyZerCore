<?php

namespace ryzerbe\core\player;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerDisconnectPacket;
use BauboLP\Cloud\Packets\PlayerMoveServerPacket;
use Exception;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\CraftingTransaction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Bow;
use pocketmine\item\Consumable;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\MaybeConsumable;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UIInventorySlotOffset;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\Player as PMPlayer;
use pocketmine\tile\Spawnable;
use pocketmine\timings\Timings;
use pocketmine\utils\TextFormat;
use ryzerbe\core\item\rod\entity\FishingHook;
use ryzerbe\core\provider\ChatEmojiProvider;
use ryzerbe\core\provider\ChatModProvider;
use ryzerbe\core\util\Settings;
use UnexpectedValueException;
use function cos;
use function count;
use function deg2rad;
use function floor;
use function microtime;
use function random_bytes;
use function sin;
use function str_replace;
use function strtolower;

class PMMPPlayer extends PMPlayer {
    /** @var array  */
    private array $delay = [];
    /** @var bool  */
    public bool $container_packet_cancel = true;

    public ?FishingHook $pvpFishingHook = null;

    public function addDelay(string $id, int|float $seconds){
        $this->delay[$id] = microtime(true) + $seconds;
    }

    /**
     * @return FishingHook|null
     */
    public function getPvpFishingHook(): ?FishingHook{
        return $this->pvpFishingHook;
    }

    /**
     * @param float|int|null $fakePitch
     * @param float|int|null $fakeYaw
     * @return Vector3
     */
    public function getLookVector(float|int|null $fakePitch = null, float|int|null $fakeYaw = null) : Vector3{
        if($fakePitch === null) {
            $y = -sin(deg2rad($this->getPitch()));
            $xz = cos(deg2rad($this->getPitch()));
        }else {
            $xz = cos(deg2rad($fakePitch));
            $y = -sin(deg2rad($fakePitch));
        }

        if($fakeYaw === null) {
            $x = -$xz * sin(deg2rad($this->getYaw()));
            $z = $xz * cos(deg2rad($this->getYaw()));
        }else {
            $x = -$xz * sin(deg2rad($fakeYaw));
            $z = $xz * cos(deg2rad($fakeYaw));
        }

        return $this->temporalVector->setComponents($x, $y, $z)->normalize();
    }

    /**
     * @return Block[]
     */
    public function getBlocksAroundOfPlayer(): array{
        $inset = 0.001; //Offset against floating-point errors

        $minX = (int) floor($this->boundingBox->minX + $inset);
        $minY = (int) floor($this->boundingBox->minY + $inset);
        $minZ = (int) floor($this->boundingBox->minZ + $inset);
        $maxX = (int) floor($this->boundingBox->maxX - $inset);
        $maxY = (int) floor($this->boundingBox->maxY - $inset);
        $maxZ = (int) floor($this->boundingBox->maxZ - $inset);

        $blocksAround = [];

        for($z = $minZ; $z <= $maxZ; ++$z){
            for($x = $minX; $x <= $maxX; ++$x){
                for($y = $minY; $y <= $maxY; ++$y){
                    $blocksAround[] = $this->level->getBlockAt($x, $y, $z);
                }
            }
        }

        return $blocksAround;
    }

    public function getEyePos() : Vector3{
        return new Vector3($this->asVector3()->x, $this->asVector3()->y + $this->getEyeHeight(), $this->asVector3()->z);
    }

    public function getBlockUnderPlayer(): Block{
        return $this->getLevel()->getBlock($this->getSide(Vector3::SIDE_DOWN));
    }

    public function getBlockOverPlayer(): Block{
        return $this->getLevel()->getBlock($this->asVector3()->add(0, 2));
    }

    /**
     * @param string $id
     */
    public function removeDelay(string $id){
        unset($this->delay[$id]);
    }

    public function hasDelay(string $id): bool{
        if(empty($this->delay[$id])) return false;

        return $this->delay[$id] > microtime(true);
    }

    public function kickFromProxy(string $reason): void{
        $pk = new PlayerDisconnectPacket();
        $pk->addData("playerName", $this->getPlayer()->getName());
        $pk->addData("message", str_replace("§", "&", $reason));
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }

    public function connectServer(string $serverName){
        $pk = new PlayerMoveServerPacket();
        $pk->addData("playerNames", $this->getPlayer()->getName());
        $pk->addData("serverName", $serverName);
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }

    public function sendToLobby(): void{
        CloudBridge::getCloudProvider()->dispatchProxyCommand($this->getPlayer()->getName(), "hub");
    }

    public function getRyZerPlayer(): ?RyZerPlayer{
        return RyZerPlayerProvider::getRyzerPlayer($this);
    }

    /**
     * Don't expect much from this handler. Most of it is roughly hacked and duct-taped together.
     */
    public function handleInventoryTransaction(InventoryTransactionPacket $packet): bool
    {
        if (!$this->spawned or !$this->isAlive()) {
            return false;
        }

        /** @var InventoryAction[] $actions */
        $actions = [];
        $isCraftingPart = false;
        foreach ($packet->trData->getActions() as $networkInventoryAction) {
            if (
                $networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO and (
                    $networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT or
                    $networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
                ) or (
                    $this->craftingTransaction !== null &&
                    !$networkInventoryAction->oldItem->getItemStack()->equalsExact($networkInventoryAction->newItem->getItemStack()) &&
                    $networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_CONTAINER &&
                    $networkInventoryAction->windowId === ContainerIds::UI &&
                    $networkInventoryAction->inventorySlot === UIInventorySlotOffset::CREATED_ITEM_OUTPUT
                )
            ) {
                $isCraftingPart = true;
            }
            try {
                $action = $networkInventoryAction->createInventoryAction($this);
                if ($action !== null) {
                    $actions[] = $action;
                }
            } catch (\UnexpectedValueException $e) {
                $this->server->getLogger()->debug("Unhandled inventory action from " . $this->getName() . ": " . $e->getMessage());
                $this->sendAllInventories();
                return false;
            }
        }

        if ($isCraftingPart) {
            if ($this->craftingTransaction === null) {
                $this->craftingTransaction = new CraftingTransaction($this, $actions);
            } else {
                foreach ($actions as $action) {
                    $this->craftingTransaction->addAction($action);
                }
            }

            try {
                $this->craftingTransaction->validate();
            } catch (TransactionValidationException $e) {
                //transaction is incomplete - crafting transaction comes in lots of little bits, so we have to collect
                //all of the parts before we can execute it
                return true;
            }

            try {
                $this->craftingTransaction->execute();
                return true;
            } catch (TransactionValidationException $e) {
                $this->server->getLogger()->debug("Failed to execute crafting transaction for " . $this->getName() . ": " . $e->getMessage());
                return false;
            } finally {
                $this->craftingTransaction = null;
            }
        } elseif ($this->craftingTransaction !== null) {
            $this->server->getLogger()->debug("Got unexpected normal inventory action with incomplete crafting transaction from " . $this->getName() . ", refusing to execute crafting");
            $this->craftingTransaction = null;
        }
        if ($packet->trData instanceof NormalTransactionData) {
            $this->setUsingItem(false);
            $transaction = new InventoryTransaction($this, $actions);

            try {
                $transaction->execute();
            } catch (TransactionValidationException $e) {
                $this->server->getLogger()->debug("Failed to execute inventory transaction from " . $this->getName() . ": " . $e->getMessage());
                $this->server->getLogger()->debug("Actions: " . json_encode($packet->trData->getActions()));

                return false;
            }

            //TODO: fix achievement for getting iron from furnace

            return true;
        } elseif ($packet->trData instanceof MismatchTransactionData) {
            if (count($packet->trData->getActions()) > 0) {
                $this->server->getLogger()->debug("Expected 0 actions for mismatch, got " . count($packet->trData->getActions()) . ", " . json_encode($packet->trData->getActions()));
            }
            $this->setUsingItem(false);
            $this->sendAllInventories();

            return true;
        } elseif ($packet->trData instanceof UseItemTransactionData) {

            $blockVector = $packet->trData->getBlockPos();
            $face = $packet->trData->getFace();
            switch ($packet->trData->getActionType()) {
                case UseItemTransactionData::ACTION_CLICK_BLOCK:
                    //TODO: start hack for client spam bug
                    $spamBug = ($this->lastRightClickData !== null and
                        microtime(true) - $this->lastRightClickTime < 0.1 and //100ms
                        $this->lastRightClickData->getPlayerPos()->distanceSquared($packet->trData->getPlayerPos()) < 0.00001 and
                        $this->lastRightClickData->getBlockPos()->equals($packet->trData->getBlockPos()) and
                        $this->lastRightClickData->getClickPos()->distanceSquared($packet->trData->getClickPos()) < 0.00001 //signature spam bug has 0 distance, but allow some error
                    );
                    //get rid of continued spam if the player clicks and holds right-click
                    $this->lastRightClickData = $packet->trData;
                    $this->lastRightClickTime = microtime(true);
                    if ($spamBug) {
                        //return true;
                    }
                    //TODO: end hack for client spam bug

                    $this->setUsingItem(false);

                    if (!$this->canInteract($blockVector->add(0.5, 0.5, 0.5), 13)) {
                    } elseif ($this->isCreative()) {
                        $item = $this->inventory->getItemInHand();
                        if ($this->useItemOn($blockVector, $item, $face, $packet->trData->getClickPos(), $this, true)) {
                            return true;
                        }
                    } elseif (!$this->inventory->getItemInHand()->equals($packet->trData->getItemInHand()->getItemStack())) {
                        $this->inventory->sendHeldItem($this);
                    } else {
                        $item = $this->inventory->getItemInHand();
                        $oldItem = clone $item;
                        if ($this->useItemOn($blockVector, $item, $face, $packet->trData->getClickPos(), $this, true)) {
                            if (!$item->equalsExact($oldItem) and $oldItem->equalsExact($this->inventory->getItemInHand())) {
                                $this->inventory->setItemInHand($item);
                                $this->inventory->sendHeldItem($this->hasSpawned);
                            }

                            return true;
                        }
                    }

                    $this->inventory->sendHeldItem($this);

                    if ($blockVector->distanceSquared($this) > 10000) {
                        return true;
                    }

                    $target = $this->level->getBlock($blockVector);
                    $block = $target->getSide($face);
                    /** @var Block[] $blocks */
                    //$blocks = array_merge($target->getAllSides(), $block->getAllSides()); //getAllSides() on each of these will include $target and $block because they are next to each other

                    $this->level->sendBlocks([$this], [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);

                    return true;
                case UseItemTransactionData::ACTION_BREAK_BLOCK:
                    $this->doCloseInventory();

                    $item = $this->inventory->getItemInHand();
                    $oldItem = clone $item;

                    if ($this->canInteract($blockVector->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 7) and $this->level->useBreakOn($blockVector, $item, $this, true)) {
                        if ($this->isSurvival()) {
                            if (!$item->equalsExact($oldItem) and $oldItem->equalsExact($this->inventory->getItemInHand())) {
                                $this->inventory->setItemInHand($item);
                                $this->inventory->sendHeldItem($this->hasSpawned);
                            }

                            $this->exhaust(0.025, PlayerExhaustEvent::CAUSE_MINING);
                        }
                        return true;
                    }

                    $this->inventory->sendContents($this);
                    $this->inventory->sendHeldItem($this);

                    $target = $this->level->getBlock($blockVector);
                    /** @var Block[] $blocks */
                    $blocks = $target->getAllSides();
                    $blocks[] = $target;

                    $this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

                    foreach ($blocks as $b) {
                        $tile = $this->level->getTile($b);
                        if ($tile instanceof Spawnable) {
                            $tile->spawnTo($this);
                        }
                    }

                    return true;
                case UseItemTransactionData::ACTION_CLICK_AIR:
                    if ($this->isUsingItem()) {
                        $slot = $this->inventory->getItemInHand();
                        if ($slot instanceof Consumable and !($slot instanceof MaybeConsumable and !$slot->canBeConsumed())) {
                            $ev = new PlayerItemConsumeEvent($this, $slot);
                            if ($this->hasItemCooldown($slot)) {
                                $ev->setCancelled();
                            }
                            $ev->call();
                            if ($ev->isCancelled() or !$this->consumeObject($slot)) {
                                $this->inventory->sendContents($this);
                                return true;
                            }
                            $this->resetItemCooldown($slot);
                            if ($this->isSurvival()) {
                                $slot->pop();
                                $this->inventory->setItemInHand($slot);
                                $this->inventory->addItem($slot->getResidue());
                            }
                            $this->setUsingItem(false);
                        }
                    }
                    $directionVector = $this->getDirectionVector();

                    if ($this->isCreative()) {
                        $item = $this->inventory->getItemInHand();
                    } elseif (!$this->inventory->getItemInHand()->equals($packet->trData->getItemInHand()->getItemStack())) {
                        $this->inventory->sendHeldItem($this);
                        return true;
                    } else {
                        $item = $this->inventory->getItemInHand();
                    }

                    $ev = new PlayerInteractEvent($this, $item, null, $directionVector, $face, PlayerInteractEvent::RIGHT_CLICK_AIR);
                    if ($this->hasItemCooldown($item) or $this->isSpectator()) {
                        $ev->setCancelled();
                    }

                    $ev->call();
                    if ($ev->isCancelled()) {
                        $this->inventory->sendHeldItem($this);
                        return true;
                    }

                    if ($item->onClickAir($this, $directionVector)) {
                        $this->resetItemCooldown($item);
                        if ($this->isSurvival()) {
                            #$this->inventory->setItemInHand($item);
                        }
                    }

                    $this->setUsingItem(true);

                    return true;
                default:
                    //unknown
                    break;
            }

            $this->inventory->sendContents($this);
            return false;
        } elseif ($packet->trData instanceof UseItemOnEntityTransactionData) {
            $target = $this->level->getEntity($packet->trData->getEntityRuntimeId());
            if ($target === null) {
                return false;
            }

            switch ($packet->trData->getActionType()) {
                case UseItemOnEntityTransactionData::ACTION_INTERACT:
                    if(!$target->isAlive()){
                        return true;
                    }
                    $ev = new PlayerInteractEntityEvent($this, $target, $item = $this->inventory->getItemInHand(), $packet->trData->getClickPos());
                    $ev->call();

                    if(!$ev->isCancelled()){
                        $oldItem = clone $item;
                        if(!$target->onFirstInteract($this, $ev->getItem(), $ev->getClickPosition())){
                            if($target instanceof Living){
                                if($this->isCreative()){
                                    $item = $oldItem;
                                }

                                if($item->onInteractWithEntity($this, $target)){
                                    if(!$item->equalsExact($oldItem) and !$this->isCreative()){
                                        $this->inventory->setItemInHand($item);
                                    }
                                }
                            }
                        }elseif(!$item->equalsExact($oldItem)){
                            $this->inventory->setItemInHand($ev->getItem());
                        }
                    }
                    break;
                case UseItemOnEntityTransactionData::ACTION_ATTACK:
                    if (!$target->isAlive()) {
                        return true;
                    }
                    if ($target instanceof ItemEntity or $target instanceof Arrow) {
                        $this->kick("Attempting to attack an invalid entity");
                        $this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidEntity", [$this->getName()]));
                        return false;
                    }

                    $cancelled = false;

                    $heldItem = $this->inventory->getItemInHand();
                    $oldItem = clone $heldItem;

                    if (!$this->canInteract($target, 8) or $this->isSpectator()) {
                        $cancelled = true;
                    } elseif ($target instanceof Player) {
                        if (!$this->server->getConfigBool("pvp")) {
                            $cancelled = true;
                        }
                    }

                    $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $heldItem->getAttackPoints());

                    $meleeEnchantmentDamage = 0;
                    /** @var EnchantmentInstance[] $meleeEnchantments */
                    $meleeEnchantments = [];
                    foreach ($heldItem->getEnchantments() as $enchantment) {
                        $type = $enchantment->getType();
                        if ($type instanceof MeleeWeaponEnchantment and $type->isApplicableTo($target)) {
                            $meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
                            $meleeEnchantments[] = $enchantment;
                        }
                    }
                    $ev->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);

                    if ($cancelled) {
                        $ev->setCancelled();
                    }

                    if (!$this->isSprinting() and !$this->isFlying() and $this->fallDistance > 0 and !$this->hasEffect(Effect::BLINDNESS) and !$this->isUnderwater()) {
                        $ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
                    }

                    $target->attack($ev);

                    if ($ev->isCancelled()) {
                        if ($heldItem instanceof Durable and $this->isSurvival()) {
                            $this->inventory->sendContents($this);
                        }
                        return true;
                    }

                    if ($ev->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0) {
                        $pk = new AnimatePacket();
                        $pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
                        $pk->entityRuntimeId = $target->getId();
                        $this->server->broadcastPacket($target->getViewers(), $pk);
                        if ($target instanceof Player) {
                            $target->dataPacket($pk);
                        }
                    }

                    foreach ($meleeEnchantments as $enchantment) {
                        $type = $enchantment->getType();
                        assert($type instanceof MeleeWeaponEnchantment);
                        $type->onPostAttack($this, $target, $enchantment->getLevel());
                    }

                    if ($this->isAlive()) {
                        //reactive damage like thorns might cause us to be killed by attacking another mob, which
                        //would mean we'd already have dropped the inventory by the time we reached here
                        if ($heldItem->onAttackEntity($target) and $this->isSurvival() and $oldItem->equalsExact($this->inventory->getItemInHand())) { //always fire the hook, even if we are survival
                            $this->inventory->setItemInHand($heldItem);
                        }

                        $this->exhaust(0.3, PlayerExhaustEvent::CAUSE_ATTACK);
                    }

                    return true;
                default:
                    break; //unknown
            }

            $this->inventory->sendContents($this);
            return false;
        } elseif ($packet->trData instanceof ReleaseItemTransactionData) {
            if($this->isOp()) $this->sendMessage("ReleaseItemTransactionData");
                switch ($packet->trData->getActionType()){
                    case ReleaseItemTransactionData::ACTION_RELEASE:
                        if($this->isOp()) $this->sendMessage("ACTION_RELEASE WILL BE HANDLE");
                        if($this->isUsingItem()){
                            $item = $this->inventory->getItemInHand();
                            if($this->hasItemCooldown($item)){
                                $this->inventory->sendContents($this);
                                if($this->isOp()) $this->sendMessage("Item Cooldown");
                                return false;
                            }
                            if($item->onReleaseUsing($this)){
                                $this->resetItemCooldown($item);
                                $this->inventory->setItemInHand($item);
                                if($this->isOp()) $this->sendMessage("Action onReleaseUsing successfully executed!");
                            }else{
                                if($this->isOp()) $this->sendMessage("Action onReleaseUsing is not in use!");
                            }
                            $this->setUsingItem(false);
                            return true;
                        }else{
                            if($this->isOp()) $this->sendMessage("isnt using item");
                        }
                        break;
                    default:
                        break;
                }


            $this->inventory->sendContents($this);
            return false;
        } else {
            $this->inventory->sendContents($this);
            return false;
        }
    }

    public function setUsingItem(bool $value){
        if($this->getInventory()->getItemInHand()->getId() === ItemIds::BOW && !$value) return;

        $this->startAction = $value ? $this->server->getTick() : -1;
        $this->setGenericFlag(self::DATA_FLAG_ACTION, $value);
    }


    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4): void{
        $f = sqrt($x * $x + $z * $z);
        if($f <= 0){
            return;
        }

        if($attacker instanceof Player) {
            if($attacker->getInventory()->getItemInHand()->hasEnchantment(Enchantment::KNOCKBACK)){
                $motion = clone $this->getMotion();
                $motion->y /= 2;
                $motion->y += 0.45;
                if($motion->y > 0.45)
                    $motion->y = 0.45;

                if(!$this->useLadder()){
                    $motion = new Vector3($attacker->getDirectionVector()->x / 1.2, $motion->y, $attacker->getDirectionVector()->z / 1.2); // ($this->getMotion()->y / 2) + 0.4
                }

                if(Settings::$reduce){
                    $ownmotion = $attacker->getMotion();
                    $ownmotion->setComponents($ownmotion->getX() * 0.6, $ownmotion->getY() * 0.6, $ownmotion->getZ() * 0.6);
                    $attacker->setMotion($ownmotion);
                    $attacker->setSprinting(false);
                }

                $this->setMotion($motion);
            }else {
                if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
                    $f = 1 / $f;

                    $motion = clone $this->motion;

                    $motion->x /= 2;
                    $motion->y /= 2;
                    $motion->z /= 2;
                    $motion->x += $x * $f * $base;
                    $motion->y += $base;
                    $motion->z += $z * $f * $base;

                    if($motion->y > $base){
                        $motion->y = $base;
                    }

                    $this->setMotion($motion);
                }
            }
        }else {
            if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
                $f = 1 / $f;

                $motion = clone $this->motion;

                $motion->x /= 2;
                $motion->y /= 2;
                $motion->z /= 2;
                if($base >= 100) {
                    $motion->x += $x * $f * 1.3;
                    $motion->y += 0.70;
                    $motion->z += $z * $f * 1.3;
                }else {
                    $motion->x += $x * $f * $base;
                    $motion->y += 0.50;
                    $motion->z += $z * $f * $base;
                }

                if($motion->y > 0.50)
                    $motion->y = 0.50;

                // var_dump($base);


                $this->setMotion($motion);
            }
        }
    }

    public function chat(string $message) : bool{
        if(!$this->spawned or !$this->isAlive()){
            return false;
        }

        $this->doCloseInventory();

        $message = TextFormat::clean($message, $this->removeFormat);
        foreach(explode("\n", $message) as $messagePart){
            if(trim($messagePart) !== "" and strlen($messagePart) <= 255 and $this->messageCounter-- > 0){
                if(str_starts_with($messagePart, './')){
                    $messagePart = substr($messagePart, 1);
                }

                $ev = new PlayerCommandPreprocessEvent($this, $messagePart);
                $ev->call();

                if($ev->isCancelled()){
                    break;
                }

                if(str_starts_with($ev->getMessage(), "/")){
                    Timings::$playerCommandTimer->startTiming();
                    $this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
                    Timings::$playerCommandTimer->stopTiming();
                }else{
                    $rbePlayer = $this->getPlayer()->getRyZerPlayer();
                    if($rbePlayer !== null && !$this->getPlayer()->hasPermission("ryzer.chatmod.bypass")) {
                        $chatMod = ChatModProvider::getInstance();
                        if($rbePlayer->getChatModData()->isSpamming()) {
                            $rbePlayer->sendTranslate("chatmod-spamming");
                            $rbePlayer->getChatModData()->lastMessageTime = microtime(true);
                            return false;
                        }

                        if(strtolower($rbePlayer->getChatModData()->getLastMessage()) === $chatMod->cleanMessageForCheck($message)) {
                            $rbePlayer->sendTranslate("chatmod-equals-message");
                            return false;
                        }

                        $rbePlayer->getChatModData()->lastMessage = $message;
                        if($chatMod->checkCaps($message)) {
                            $rbePlayer = $this->getPlayer()->getRyZerPlayer();
                            $message = strtolower($message);
                        }
                        $message = $chatMod->replaceDuplicatedCharacters($message);
                        if($provocation = $chatMod->checkProvocation($message)) {
                            $replaceMessage = $chatMod->replaceBadWords($message, $provocation);
                            if($replaceMessage !== false) {
                                $message = $replaceMessage;
                            }else {
                                $rbePlayer->sendTranslate("chatmod-provocation");
                                return false;
                            }
                        }

                        if(count($chatMod->checkDomain($message)) > 0) {
                            $rbePlayer->sendTranslate("chatmod-domain");
                            return false;
                        }

                        if($badWords = $chatMod->checkForbiddenWord($message)) {
                            $replaceMessage = $chatMod->replaceBadWords($message, $badWords);
                            if($replaceMessage !== false) {
                                $message = $replaceMessage;
                            }else {
                                $rbePlayer->sendTranslate("chatmod-forbidden-word");
                                return false;
                            }
                        }
                        $rbePlayer->getChatModData()->lastMessageTime = microtime(true);
                    }

                    $message = ChatEmojiProvider::getInstance()->replaceKeys($message);
                    $ev = new PlayerChatEvent($this, $message);
                    $ev->call();
                    if(!$ev->isCancelled()){
                        $this->server->broadcastMessage($this->getServer()->getLanguage()->translateString($ev->getFormat(), [$ev->getPlayer()->getDisplayName(), $message]), $ev->getRecipients());
                    }
                }
            }
        }

        return true;
    }

    public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool{
        if(!$this->constructed or $packet->entityUniqueId !== $this->getId()){
            return false;
        }

        $handled = false;

        $isFlying = $packet->getFlag(AdventureSettingsPacket::FLYING);
        if($isFlying and !$this->allowFlight){
            //  $this->kick($this->server->getLanguage()->translateString("kick.reason.cheat", ["%ability.flight"]));
            return true;
        }elseif($isFlying !== $this->isFlying()){
            $ev = new PlayerToggleFlightEvent($this, $isFlying);
            $ev->call();
            if($ev->isCancelled()){
                $this->sendSettings();
            }else{ //don't use setFlying() here, to avoid feedback loops
                $this->flying = $ev->isFlying();
                $this->resetFallDistance();
            }

            $handled = true;
        }

        if($packet->getFlag(AdventureSettingsPacket::NO_CLIP) and !$this->allowMovementCheats and !$this->isSpectator()){
            $this->kick($this->server->getLanguage()->translateString("kick.reason.cheat", ["%ability.noclip"]));
            return true;
        }

        return $handled;
    }

    public function dropItemForPlayer(Position $source, Item $item, Vector3 $motion = null, int $delay = 10): ?ItemEntity{
        $motion = $motion ?? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1);
        $itemTag = $item->nbtSerialize();
        $itemTag->setName("Item");

        if(!$item->isNull()){
            $nbt = Entity::createBaseNBT($source->asVector3(), $motion, lcg_value() * 360, 0);
            $nbt->setShort("Health", 5);
            $nbt->setShort("PickupDelay", $delay);
            $nbt->setTag($itemTag);
            $itemEntity = Entity::createEntity("Item", $source->getLevel(), $nbt);

            if($itemEntity instanceof ItemEntity){
                $itemEntity->spawnTo($this);

                return $itemEntity;
            }
        }
        return null;
    }

    public function useItemOn(Vector3 $vector, Item &$item, int $face, Vector3 $clickVector = null, Player $player = null, bool $playSound = false) : bool{
        $blockClicked = $this->getLevel()->getBlock($vector);
        $blockReplace = $blockClicked->getSide($face);

        if($clickVector === null){
            $clickVector = new Vector3(0.0, 0.0, 0.0);
        }

        if(!$this->getLevel()->isInWorld($blockReplace->x, $blockReplace->y, $blockReplace->z)){
            //TODO: build height limit messages for custom world heights and mcregion cap
            return false;
        }

        if($blockClicked->getId() === BlockIds::AIR){
            return false;
        }

        if($player !== null){
            $ev = new PlayerInteractEvent($player, $item, $blockClicked, $clickVector, $face, PlayerInteractEvent::RIGHT_CLICK_BLOCK);
            if($this->getLevel()->checkSpawnProtection($player, $blockClicked) or $player->isSpectator()){
                $ev->setCancelled(); //set it to cancelled so plugins can bypass this
            }

            $ev->call();
            if(!$ev->isCancelled()){
                if((!$player->isSneaking() or $item->isNull()) and $blockClicked->onActivate($item, $player)){
                    return true;
                }

                if($item->onActivate($player, $blockReplace, $blockClicked, $face, $clickVector)){
                    return true;
                }
            }else{
                return false;
            }
        }elseif($blockClicked->onActivate($item, $player)){
            return true;
        }

        if($item->canBePlaced()){
            $hand = $item->getBlock();
            $hand->position($blockReplace);
        }else{
            return false;
        }

        if($hand->canBePlacedAt($blockClicked, $clickVector, $face, true)){
            $blockReplace = $blockClicked;
            $hand->position($blockReplace);
        }elseif(!$hand->canBePlacedAt($blockReplace, $clickVector, $face, false)){
            return false;
        }

        if($hand->isSolid()){
            foreach($hand->getCollisionBoxes() as $collisionBox){
                foreach($this->getLevel()->getCollidingEntities($collisionBox) as $collidingEntity){
                    if($collidingEntity instanceof ItemEntity) continue;
                    return false;
                }
            }
        }

        if($player !== null){
            $ev = new BlockPlaceEvent($player, $hand, $blockReplace, $blockClicked, $item);
            if($this->getLevel()->checkSpawnProtection($player, $blockReplace) or $player->isSpectator()){
                $ev->setCancelled();
            }

            if($player->isAdventure(true) and !$ev->isCancelled()){
                $canPlace = false;
                $tag = $item->getNamedTagEntry("CanPlaceOn");
                if($tag instanceof ListTag){
                    foreach($tag as $v){
                        if($v instanceof StringTag){
                            $entry = ItemFactory::fromStringSingle($v->getValue());
                            if($entry->getId() > 0 and $entry->getBlock()->getId() === $blockClicked->getId()){
                                $canPlace = true;
                                break;
                            }
                        }
                    }
                }

                $ev->setCancelled(!$canPlace);
            }

            $ev->call();
            if($ev->isCancelled()){
                return false;
            }
        }

        if(!$hand->place($item, $blockReplace, $blockClicked, $face, $clickVector, $player)){
            return false;
        }

        if($playSound){
            $this->getLevel()->broadcastLevelSoundEvent($hand, LevelSoundEventPacket::SOUND_PLACE, $hand->getRuntimeId());
        }

        $item->pop();

        return true;
    }


    public function applyDamageModifiers(EntityDamageEvent $source): void{
        if($this->getPlayer() == null) return;
        if($this->getPlayer()->isClosed()) return;

        parent::applyDamageModifiers($source);
    }

    public function useLadder(): bool{
        foreach ($this->getBlocksAround() as $block) {
            if($block->getId() === BlockIds::LADDER) return true;
        }
        return false;
    }
}