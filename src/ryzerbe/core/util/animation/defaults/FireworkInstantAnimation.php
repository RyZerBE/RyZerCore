<?php

namespace ryzerbe\core\util\animation\defaults;

use pocketmine\entity\Entity;
use pocketmine\entity\object\FireworksRocket;
use pocketmine\item\Fireworks;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use ryzerbe\core\util\animation\Animation;
use ryzerbe\core\util\TaskUtils;
use function lcg_value;

class FireworkInstantAnimation extends Animation {

    /** @var Position[]  */
    private array $positions;

    private int $ticks;

    /**
     * @return Fireworks
     */
    private function getFireworks(): Fireworks{
        /** @var Fireworks $firework */
        $firework = ItemFactory::get(ItemIds::FIREWORKS);

        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_BLACK, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_RED, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_DARK_GREEN, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_BROWN, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_BLUE, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_DARK_PURPLE, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_DARK_AQUA, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_GRAY, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_DARK_GRAY, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_PINK, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_GREEN, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_YELLOW, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_LIGHT_AQUA, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_DARK_PINK, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_GOLD, "", true, true);
        $firework->addExplosion(mt_rand(0, 4), Fireworks::COLOR_WHITE, "", true, true);
        return $firework;
    }

    /**
     * @param Position[] $positions
     */
    public function __construct(array $positions, int $seconds){
        $this->positions = $positions;
        $this->ticks = TaskUtils::secondsToTicks($seconds);
        parent::__construct();
    }

    public function tick(): void{
        if($this->ticks === $this->getCurrentTick()) $this->cancel();
        foreach($this->positions as $position) {
            $center = $position->add(mt_rand(-4, 4) + lcg_value(), $position->y + lcg_value(), mt_rand(-4, 4) + lcg_value());

            $entity = new FireworksRocket($position->getLevelNonNull(), Entity::createBaseNBT($center, null, lcg_value() * 360, 90), $this->getFireworks());
            $entity->spawnToAll();
        }
        parent::tick();
    }
}