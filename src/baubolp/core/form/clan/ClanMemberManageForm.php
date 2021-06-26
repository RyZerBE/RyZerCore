<?php


namespace baubolp\core\form\clan;


use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClanMemberManageForm extends MenuForm
{

    public function __construct(array $members, bool $manageRole)
    {
        $options = [];
        $clanMembers = [];
        foreach ($members as $member) {
           if($member != "") {
               $options[] = new MenuOption(TextFormat::GREEN.$member); //TextFormat::GRAY." [".TextFormat::GOLD.$i[1].TextFormat::GRAY."]"
               $clanMembers[] = $member; //TextFormat::GRAY." [".TextFormat::GOLD.$i[1].TextFormat::GRAY."]"
           }
        }
        parent::__construct(Ryzer::PREFIX.TextFormat::RED."Clans", "", $options, function (Player $player, int $selectedOption) use ($clanMembers, $manageRole): void{
            $clanMember = $clanMembers[$selectedOption];
            $player->sendForm(new ClanMemberOptionForm($clanMember, $manageRole));
        });
    }
}