<?php


namespace baubolp\core\form\clan;


use BauboLP\Cloud\CloudBridge;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanColorForm extends MenuForm
{

    public function __construct(string $clanTag)
    {
        $colors = ["&1", "&2", "&3", "&4", "&5", "&6", "&7", "&8", "&9", "&f", "&e", "&d", "&c", "&a", "&b"];
        $options = [];
        foreach ($colors as $color) {
            $options[] = new MenuOption(str_replace("&", TextFormat::ESCAPE, $color).$clanTag);
        }
        parent::__construct(Ryzer::PREFIX.TextFormat::RED."Clans", "", $options, function (Player $player, int $selectedOption) use ($colors): void {
            $color = $colors[$selectedOption];
            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan color $color");
        });
    }
}