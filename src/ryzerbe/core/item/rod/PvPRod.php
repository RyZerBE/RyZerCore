<?php

namespace ryzerbe\core\item\rod;

use pocketmine\entity\Entity;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Durable;
use pocketmine\item\ItemIds;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\Player;
use ryzerbe\core\item\rod\entity\FishingHook;
use ryzerbe\core\player\PMMPPlayer;

class PvPRod extends Durable {

    public function __construct(){
        parent::__construct(ItemIds::FISHING_ROD, 0, "PvP Rod");
    }

    /**
     * @return int
     */
    public function getMaxDurability(): int{
        return 355;
    }

    public function getMaxStackSize(): int{
        return 1;
    }

    public function onClickAir(Player $player, Vector3 $directionVector): bool{
        if(!$player instanceof PMMPPlayer) return false;
        $fishingHook = $player->getPvpFishingHook();

        if($fishingHook === null) {
            $fishingHook = new FishingHook($player->getLevel(), Entity::createBaseNBT($player->getEyePos(), $player->getDirectionVector(), $player->yaw, $player->pitch), $player);
            $fishingHook->throwHook($this);

            $ev = new ProjectileLaunchEvent($fishingHook);
            $ev->call();
            if($ev->isCancelled()) {
                $fishingHook->flagForDespawn();
                return false;
            }

            $fishingHook->spawnToAll();
            $player->getLevel()->addSound(new LaunchSound($player->asVector3()), [$player]);
            return true;
        }

        $fishingHook->restrictHook();
        $this->applyDamage(1);
        return true;
    }

    public function getThrowForce(): float{
        return 1.9;
    }
}