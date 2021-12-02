<?php

namespace ryzerbe\core\util\animation\defaults;

use Closure;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\level\Position;
use pocketmine\Player;
use ryzerbe\core\entity\SkullEntity;

class FireworkPlayerHeadWinnerAnimation extends FireworkInstantAnimation {

    /** @var SkullEntity|null  */
    public ?SkullEntity $skullEntity = null;

    public function __construct(array $positions, int $seconds, private Position $headPosition, private Skin $skin, private ?Closure $spawnedClosure = null){
        parent::__construct($positions, $seconds);
    }

    public function tick(): void{
        parent::tick();
        if($this->ticks === $this->getCurrentTick()){
            $this->skullEntity->flagForDespawn();
            $this->cancel();
            return;
        }
        $headPosition = $this->headPosition;
        if($this->skullEntity === null) {
            $nbt = Entity::createBaseNBT($headPosition);
            $skullEntity = new SkullEntity($headPosition->getLevelNonNull(), $nbt, $this->skin, 1.7, function(SkullEntity $skullEntity, Player $player): void{
                //maybe send round stats of the player...
            });
            //todo: maybe improve because in my head it looks like better than i did it. use /test to spawn it!
            $skullEntity->spawnToAll();
            $this->skullEntity = $skullEntity;
            $closure = $this->spawnedClosure;
            $closure($this->skullEntity);
        }

        if($this->getCurrentTick() % 10 === 0){
            $this->skullEntity->yaw += 20;
            $this->skullEntity->setForceMovementUpdate(true);
        }
    }
}