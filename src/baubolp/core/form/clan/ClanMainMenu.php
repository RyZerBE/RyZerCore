<?php


namespace baubolp\core\form\clan;


use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\utils\TextFormat;

class ClanMainMenu extends MenuForm
{

    public function __construct(array $options, \Closure $onSubmit)
    {
        parent::__construct(Ryzer::PREFIX.TextFormat::RED."Clans", "", $options, $onSubmit);
    }
}