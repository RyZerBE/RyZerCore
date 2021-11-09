<?php

namespace ryzerbe\core\form\types;

use Closure;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ConfirmationForm {

    /**
     * @param Player $player
     * @param string $text
     * @param Closure $confirmClosure
     * @param Closure|null $abortClosure
     * @param Closure|null $closeClosure
     */
    public static function onOpen(Player $player, string $text, Closure $confirmClosure, ?Closure $abortClosure = null, ?Closure $closeClosure = null){
        $form = new SimpleForm(function(Player $player, $data) use ($confirmClosure, $abortClosure, $closeClosure): void{
            if($data === null) {
                if($closeClosure !== null) {
                    $closeClosure($player);
                }
                return;
            }

            switch($data) {
                case "confirm":
                    if($confirmClosure !== null) {
                        $confirmClosure($player);
                    }
                    break;
                case "abort":
                    if($abortClosure !== null) {
                        $abortClosure($player);
                    }
                    break;
            }
        });

        $form->setContent($text);
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Confirmation");
        $form->addButton(TextFormat::GREEN."✔ Confirm",0, "textures/ui/confirm.png", "confirm");
        $form->addButton(TextFormat::GREEN."✔ Abort",0, "textures/ui/realms_red_x.png", "abort");
        $form->sendToPlayer($player);
    }
}