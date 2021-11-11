<?php

namespace ryzerbe\core\form\types\clan;

use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\RyZerBE;

class ClanTop10Form extends MenuForm {
    /**
     * ClanTop10Form constructor.
     *
     * @param MenuOption[] $options
     */
    public function __construct(array $options){
        parent::__construct(RyZerBE::PREFIX . TextFormat::RED . "Clans", "", $options, function(Player $player, int $selectedOption): void{
        });
    }
}