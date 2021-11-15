<?php

namespace ryzerbe\core\entity;

use pocketmine\entity\DataPropertyManager;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;

class Hologram extends FloatingTextParticle {

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

    public function spawn(Level $level): void{
        $level->addParticle($this);
    }

    /**
     * @param string $title
     * @param string $text
     * @param array $players
     */
    public function rename(string $title, string $text, array $players): void{
        $actorPacket = new SetActorDataPacket();
        $actorPacket->entityRuntimeId = $this->getEntityId();

        $dataPropertyManager = new DataPropertyManager();
        if($title == "")
            $dataPropertyManager->setString(Entity::DATA_NAMETAG, $text);
        else
            $dataPropertyManager->setString(Entity::DATA_NAMETAG, $title."\n".$text);

        $actorPacket->metadata = $dataPropertyManager->getAll();
        foreach($players as $player)
            $player->sendDataPacket($actorPacket);
    }
}