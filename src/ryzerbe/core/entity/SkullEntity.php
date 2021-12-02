<?php

namespace ryzerbe\core\entity;

use Closure;
use pocketmine\block\BlockIds;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\tile\Skull as SkullTile;

class SkullEntity extends Human implements ChunkLoader {
    public const HEAD_GEOMETRY = '{"format_version": "1.12.0", "minecraft:geometry": [{"description": {"identifier": "geometry.player_head", "texture_width": 64, "texture_height": 64, "visible_bounds_width": 2, "visible_bounds_height": 4, "visible_bounds_offset": [0, 0, 0]}, "bones": [{"name": "Head", "pivot": [0, 24, 0], "cubes": [{"origin": [-4, 0, -4], "size": [8, 8, 8], "uv": [0, 0]}, {"origin": [-4, 0, -4], "size": [8, 8, 8], "inflate": 0.5, "uv": [32, 0]}]}]}]}';

    /** @var float  */
    public $width = 0.1;
    /** @var float  */
    public $height = 0.1;
    /** @var float  */
    public float $scale = 1.1;

    /**
     * @var Closure|null
     * function (SkullEntity $skullEntity, Player $player): void{}
     */
    public ?Closure $interactClosure = null;

    public function __construct(Level $level, CompoundTag $nbt, ?Skin $skin = null, float $scale = 1.1, ?Closure $interactClosure = null){
        if($skin === null) return;
        $this->skin = $skin;
        $this->interactClosure = $interactClosure;
        $this->scale = $scale;
        parent::__construct($level, $nbt);
        unset($level->updateEntities[$this->getId()]);
    }

    public function initEntity(): void{
        parent::initEntity();
        $this->setScale($this->scale);
        $this->setSkin($this->getSkin());
    }

    public function setSkin(Skin $skin) : void {
        parent::setSkin(new Skin($skin->getSkinId(), $skin->getSkinData(), "", "geometry.player_head", self::HEAD_GEOMETRY));
    }

    public function __sendSpawnPacket(Player $player): void {
        parent::sendSpawnPacket($player);
        $this->validatePosition();
    }

    public function onUpdate(int $currentTick): bool {
        if($this->isFlaggedForDespawn()) return true;
        if($this->forceMovementUpdate) $this->updateMovement();
        #$this->validatePosition();
        return false;
    }

    private function validatePosition(): void {
        $tile = $this->getLevel()->getTile($this);
        if($tile === null || $tile->isClosed()){
            $block = $this->getLevel()->getBlock($this);
            if($block->getId() !== BlockIds::SKULL_BLOCK) return;
            $tile = Tile::createTile("Skull", $this->getLevel(), SkullTile::createNBT($this));
        }
        $vector = $this->floor()->add(0.5, -0.01, 0.5);
        $damage = $this->getLevel()->getBlock($this)->getDamage();
        switch ($damage) {
            case 1: {
                $rot = 0;
                if(!is_null($tile)) $rot = $tile->getCleanedNBT()->getByte(SkullTile::TAG_ROT);
                $rotations = [0 => 180, 15 => 157.5, 14 => 135, 13 => 112.5, 12 => 90, 11 => 67.5, 10 => 45, 9 => 22.5, 8 => 360, 7 => 337.5, 6 => 315, 5 => 292.5, 4 => 270, 3 => 247.5, 2 => 225, 1 => 202.5];
                $this->teleport($vector, ($rotations[$rot] ?? 0), 0);
                break;
            }
            default: {
                $values = [2 => ["X" => 0, "Z" => 0.24, "Yaw" => 180], 3 => ["X" => 0, "Z" => -0.24, "Yaw" => 0], 4 => ["X" => 0.24, "Z" => 0, "Yaw" => 90], 5 => ["X" => -0.24, "Z" => 0, "Yaw" => 270]];
                $this->teleport($vector->add($values[$damage]["X"], 0.24, $values[$damage]["Z"]), $values[$damage]["Yaw"], 0);
            }
        }
    }

    public function canSaveWithChunk(): bool{
        return false;
    }

    public function hasMovementUpdate() : bool{
        return false;
    }

    public function startDeathAnimation(): void {}
    public function attack(EntityDamageEvent $source): void{
        if(!$source instanceof EntityDamageByEntityEvent) return;
        $player = $source->getDamager();
        iF(!$player instanceof Player) return;

        $function = $this->interactClosure;
        $function($this, $player);
    }

    public function getLoaderId(): int {
        return spl_object_id($this);
    }

    public function isLoaderActive(): bool {
        return !$this->isClosed();
    }

    public function onChunkChanged(Chunk $chunk){}
    public function onChunkLoaded(Chunk $chunk){}
    public function onChunkUnloaded(Chunk $chunk){}
    public function onChunkPopulated(Chunk $chunk){}
    public function onBlockChanged(Vector3 $block){}
}