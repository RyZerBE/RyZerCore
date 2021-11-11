<?php

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\core\provider\StaffProvider;

class RyZerPlayerAuthListener implements Listener {
    public function auth(RyZerPlayerAuthEvent $event): void{
        $player = $event->getRyZerPlayer()->getPlayer();
        if(!$player->hasPermission("ryzer.login") && StaffProvider::loggedIn($player)) {
            StaffProvider::logout($player);
        }
    }
}