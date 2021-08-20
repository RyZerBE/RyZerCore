<?php


namespace baubolp\core\listener\own;


use pocketmine\event\Event;
use pocketmine\Player;
use pocketmine\utils\Config;

class EditionFakerEvent extends Event
{
    /** @var Player */
    private Player $player;
    /** @var int */
    private int $input;
    /** @var int */
    private int $os;
    /** @var string  */
    private string $name;

    /**
     * EditionFakerEvent constructor.
     * @param Player $player
     * @param $input
     * @param $device
     */
    public function __construct(Player $player, int $input, int $device)
    {
        $this->os = $device;
        $this->input = $input;
        $this->player = $player;
        $this->name = $player->getName();
    }

    /**
     * @return bool
     */
    public function hasFaker(): bool {
        return $this->getOs() == 1 && $this->getInput() == 1;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return mixed
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @return mixed
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return array
     */
    public function getWhitelist(): array {
        if(!is_file("/root/Cloudsystemneu/data/editionfaker.json")) {
            $c = new Config("/root/RyzerCloud/data/editionfaker.json", Config::JSON);
            $c->set("whitelist", []);
            $c->save();
            return [];
        }

        $c = new Config("/root/RyzerCloud/data/editionfaker.json", Config::JSON);
        return $c->get("whitelist");
    }

    /**
     * @return bool
     */
    public function isWhitelisted(): bool {
        return in_array($this->getPlayerName(), $this->getWhitelist());
    }

    /**
     * @return mixed
     */
    public function getPlayerName()
    {
        return $this->name;
    }
}