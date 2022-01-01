<?php

namespace ryzerbe\core\util\scoreboard;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use ryzerbe\core\player\RyZerPlayer;

class Scoreboard {
    private const SORT_ASCENDING = 0;
    private const SLOT_SIDEBAR = "sidebar";
    private const CRITERIA_NAME = "dummy";

    private RyZerPlayer $player;
    private string $title;
    private ?string $imagePath; //provided our resource pack
    /** @var ScorePacketEntry[] */
    private array $lines = [];

    public function __construct(RyZerPlayer $player, string $title, ?string $titleImage = null){
        $this->player = $player;
        $this->title = $title;
        $this->imagePath = $titleImage;
        $this->initScoreboard();
    }

    private function initScoreboard(): void{
        $pkt = new SetDisplayObjectivePacket();
        $pkt->objectiveName = $this->player->getPlayer()->getName();
        $pkt->displayName = ($this->imagePath !== null) ? $this->imagePath : $this->title;
        $pkt->sortOrder = self::SORT_ASCENDING;
        $pkt->displaySlot = self::SLOT_SIDEBAR;
        $pkt->criteriaName = self::CRITERIA_NAME;
        $this->player->getPlayer()->dataPacket($pkt);
    }

    public function clearScoreboard(): void{
        $pkt = new SetScorePacket();
        $pkt->entries = $this->lines;
        $pkt->type = SetScorePacket::TYPE_REMOVE;
        $this->player->getPlayer()->dataPacket($pkt);
        $this->lines = [];
    }


    public function removeLine(int $id): void{
        if(isset($this->lines[$id])){
            $line = $this->lines[$id];
            $packet = new SetScorePacket();
            $packet->entries[] = $line;
            $packet->type = SetScorePacket::TYPE_REMOVE;
            $this->player->getPlayer()->dataPacket($packet);
            unset($this->lines[$id]);
        }
    }

    public function removeScoreboard(): void{
        $packet = new RemoveObjectivePacket();
        $packet->objectiveName = $this->player->getPlayer()->getName();
        $this->player->getPlayer()->dataPacket($packet);
    }

    public function setLines(array $lines): void{
        foreach($lines as $key => $line) $this->addLine($key, $line);
    }

    public function addLine(int $id, string $line): void{
        $entry = new ScorePacketEntry();
        $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
        if(isset($this->lines[$id])){
            $pkt = new SetScorePacket();
            $pkt->entries[] = $this->lines[$id];
            $pkt->type = SetScorePacket::TYPE_REMOVE;
            $this->player->getPlayer()->dataPacket($pkt);
            unset($this->lines[$id]);
        }
        $entry->score = $id;
        $entry->scoreboardId = $id;
        $entry->entityUniqueId = $this->player->getPlayer()->getId();
        $entry->objectiveName = $this->player->getPlayer()->getName();
        $entry->customName = $line;
        $this->lines[$id] = $entry;
        $pkt = new SetScorePacket();
        $pkt->entries[] = $entry;
        $pkt->type = SetScorePacket::TYPE_CHANGE;
        $this->player->getPlayer()->dataPacket($pkt);
    }

    public function getPlayer(): RyZerPlayer{
        return $this->player;
    }
}