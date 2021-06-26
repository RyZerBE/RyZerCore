<?php


namespace baubolp\core\form\clan;


use BauboLP\Cloud\CloudBridge;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanRolesForm extends MenuForm
{

    public function __construct(string $clanMember)
    {
        $roles = ["Member", "Moderator", "Leader"];
        $options = [];
        foreach ($roles as $role) {
            $options[] = new MenuOption(TextFormat::GOLD.$role);
        }
        parent::__construct(Ryzer::PREFIX.TextFormat::RED."Clans", "", $options, function (Player $player, int $selectedOption) use ($roles, $clanMember): void{
            $role = $roles[$selectedOption];
            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan role $clanMember $role");
        });
    }
}