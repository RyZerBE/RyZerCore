<?php

namespace ryzerbe\core\form\types;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class VerifyForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $token = $extraData["token"] ?? "???";
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null || $data === "token") return;
            $form = new SimpleForm(function(Player $player, $data): void{
            });
            $form->addButton(TextFormat::GREEN . "Join our discord\ndiscord.ryzer.be", 1, "https://media.discordapp.net/attachments/602115215307309066/907594440762351616/number_1_blue-512.png?width=410&height=410");
            $form->addButton(TextFormat::GREEN . "Go to the #verify channel", 1, "https://media.discordapp.net/attachments/602115215307309066/907594595154681876/846403_blue_512x512.png?width=410&height=410");
            $form->addButton(TextFormat::GREEN . "Use &verify <Your token>", 1, "https://media.discordapp.net/attachments/602115215307309066/907594723785592832/number_3_blue-512.png?width=410&height=410");
            $form->addButton(TextFormat::GREEN . "Help? Use #ticket-support", 1, "https://media.discordapp.net/attachments/602115215307309066/907594817876422717/support-131964752580156495.png?width=410&height=410");
            $form->sendToPlayer($player);
        });
        $form->addButton(TextFormat::YELLOW . $token, 1, "https://media.discordapp.net/attachments/602115215307309066/907592041616269352/891399.png?width=410&height=410", "token");
        $form->addButton(TextFormat::RED . "How do I verify my account?", 1, "https://media.discordapp.net/attachments/602115215307309066/907593311756386325/icon-design-guide.png?width=256&height=270", "guide");
        $form->sendToPlayer($player);
    }
}