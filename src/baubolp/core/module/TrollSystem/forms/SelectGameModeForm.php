<?php


namespace baubolp\core\module\TrollSystem\forms;


use baubolp\core\module\TrollSystem\TrollSystem;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SelectGameModeForm extends MenuForm
{

    public function __construct()
    {
        $options = [new MenuOption(TextFormat::RED."Survial"), new MenuOption(TextFormat::RED."Creative"), new MenuOption(TextFormat::RED."Adventure"), new MenuOption(TextFormat::RED."Spectator")];
        parent::__construct(TrollSystem::Prefix.TextFormat::YELLOW."GameMode", "", $options, function (Player $player, int $selectedOption): void{
             $player->setGamemode($selectedOption);
        });
    }
}