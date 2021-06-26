<?php


namespace baubolp\core\form\clan;


use BauboLP\Cloud\CloudBridge;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanMemberOptionForm extends MenuForm
{

    public function __construct(string $clanMember, bool $manageRole)
    {
        $options = [];
        $options[] = new MenuOption(TextFormat::RED."Kick Player");

        if($manageRole)
        $options[] = new MenuOption(TextFormat::RED."Manage Role");

        parent::__construct(Ryzer::PREFIX.TextFormat::RED."Clans", "", $options, function (Player $player, int $selectedOption) use ($clanMember): void{
            if($selectedOption == 0) {
                CloudBridge::getCloudProvider()->dispatchProxyCommand($clanMember, "clan kick $clanMember");
            }else if($selectedOption == 1) {
                $player->sendForm(new ClanRolesForm($clanMember));
            }
        });
    }

}