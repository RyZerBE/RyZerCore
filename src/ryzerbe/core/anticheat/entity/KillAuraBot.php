<?php

namespace ryzerbe\core\anticheat\entity;

use pocketmine\entity\Human;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\anticheat\AntiCheatManager;
use function microtime;

class KillAuraBot extends Human {

    public float|int $spawned;
    protected $gravity = 0;

    public function __construct(Level $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);
        $this->spawned = microtime(true);
    }

    /**
     * @param Vector3 $pos
     * @param float|null $yaw
     * @param float|null $pitch
     * @return void
     */
    public function sendPosition(Vector3 $pos, float $yaw = null, float $pitch = null)
    {
        $yaw = $yaw ?? $this->yaw;
        $pitch = $pitch ?? $this->pitch;

        $pk = new MoveActorAbsolutePacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $pos;
        $pk->xRot = $pitch;
        $pk->yRot = $yaw; 
        $pk->zRot = $yaw;
        $pk->flags = MoveActorAbsolutePacket::FLAG_GROUND;
        $this->server->broadcastPacket($this->hasSpawned, $pk);
    }

    protected function checkChunks(): void{
        $chunkX = $this->getFloorX() >> 4;
        $chunkZ = $this->getFloorZ() >> 4;
        if($this->chunk === null or ($this->chunk->getX() !== $chunkX or $this->chunk->getZ() !== $chunkZ)){
            $this->chunk?->removeEntity($this);
            $this->chunk = $this->level->getChunk($chunkX, $chunkZ, true);

            if(!$this->justCreated){
                $newChunk = $this->level->getViewersForPosition($this);
                foreach($this->hasSpawned as $player){
                    if(!isset($newChunk[$player->getLoaderId()])){
                        $this->despawnFrom($player);
                    }else{
                        unset($newChunk[$player->getLoaderId()]);
                    }
                }
                foreach($newChunk as $player){
                    if (!in_array($player, $this->getViewers())) continue;
                    $this->spawnTo($player);
                }
            }

            if($this->chunk === null){
                return;
            }

            $this->chunk->addEntity($this);
        }
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ((microtime(true) - $this->spawned) > 3) $this->flagForDespawn();
        return parent::entityBaseTick($tickDiff);
    }

    public function flagForDespawn(): void
    {
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        $pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
        Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

        $checkPlayer = $this->namedtag->getString("checkPlayer", "DeineMuddaIstRichtigFettLPYTHD");
        $player = Server::getInstance()->getPlayer($checkPlayer);
        if($player !== null) {
            $acPlayer = AntiCheatManager::getPlayer($player);
            if($acPlayer !== null) {
                $acPlayer->killAuraBot = null;
            }
        }
        parent::flagForDespawn();
    }

    public function moveToPlayer(Player $player){
        $pos = $player->getDirectionPlane()->multiply(-2);
        $vector = $player->getPosition()->add(
            $pos->getX(), 1, $pos->getY()
        );
        $this->sendPosition($vector, $this->getYaw(), $this->getPitch());
    }
}