<?php


namespace baubolp\core\form;


use baubolp\core\Ryzer;
use pocketmine\form\FormIcon;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class VerifyForm extends MenuForm
{

    public function __construct(string $token, bool $isVerified)
    {
        $options = [];

        $options[] = new MenuOption(TextFormat::GREEN."Token: ".TextFormat::AQUA.$token, new FormIcon("https://media.discordapp.net/attachments/570704629721989122/704712257283555359/images.png", FormIcon::IMAGE_TYPE_URL));

        $icon = ($isVerified == true) ? "textures/ui/confirm.png" : "textures/ui/realms_red_x.png";
        $options[] = new MenuOption(($isVerified == true) ? TextFormat::GREEN."VERIFIED" : TextFormat::RED.TextFormat::BOLD."NOT VERIFIED", new FormIcon($icon, FormIcon::IMAGE_TYPE_PATH));

        parent::__construct(Ryzer::PREFIX.TextFormat::YELLOW."Verification", "", $options, function (Player $player, int $selectedOption): void{});
    }
}