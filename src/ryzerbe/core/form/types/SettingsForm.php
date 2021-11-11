<?php

namespace ryzerbe\core\form\types;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\RyZerPlayerProvider;

class SettingsForm {
    public static function onOpen(Player $player): void{
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($ryzerPlayer === null) return;
        $form = new CustomForm(function(Player $player, $data) use ($ryzerPlayer): void{
            if($data === null) return;
            $moreParticle = $data["more_particle"];
            $ryzerPlayer->getPlayerSettings()->setMoreParticle($moreParticle);
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        });
        $form->addToggle(TextFormat::YELLOW . "More Particle", $ryzerPlayer->getPlayerSettings()->isMoreParticleActivated(), "more_particle");
        $form->sendToPlayer($player);
    }
}