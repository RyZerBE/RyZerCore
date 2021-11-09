<?php


namespace ryzerbe\core\form\types\clan;


use BauboLP\Cloud\CloudBridge;
use pocketmine\form\ModalForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\RyZerBE;

class ChooseEloOrFunForm extends ModalForm
{
    
    public function __construct()
    {
        parent::__construct(RyZerBE::PREFIX.TextFormat::RED."Clans", "", function (Player $player, bool $choice):void{
            if($choice) {
                CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan cw elo");
            }else {
                CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan cw fun");
            }
        }, TextFormat::GOLD."Elo ClanWar", TextFormat::GREEN."Fun ClanWar");
    }
}