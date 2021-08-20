<?php


namespace baubolp\core\entity;


use baubolp\core\Ryzer;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class HoloGram extends FloatingTextParticle
{

    public function __construct(Vector3 $pos, string $text, string $title = "")
    {
        parent::__construct($pos, $text, $title);
    }

    /**
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    /**
     * @param string $text
     * @param string $title
     * @param Player[] $players
     */
    public function update(string $text, string $title, array $players = [])
    {
        if(count($players) <= 0 || in_array("ALL", $players))
            $players = Server::getInstance()->getOnlinePlayers();

        Ryzer::renameEntity($this->getEntityId(), $text, $title, $players);
    }
}