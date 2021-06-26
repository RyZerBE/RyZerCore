<?php


namespace baubolp\core\form\clan;


use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanTop10Form extends MenuForm
{
    /**
     * ClanTop10Form constructor.
     *
     * @param \pocketmine\form\MenuOption[] $options
     */
    public function __construct(array $options)
    {
        parent::__construct(Ryzer::PREFIX.TextFormat::RED."Clans", "", $options, function (Player $player, int $selectedOption):void{});
    }
}