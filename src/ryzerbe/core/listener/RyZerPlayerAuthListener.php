<?php

namespace ryzerbe\core\listener;

use pocketmine\event\Listener;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\core\provider\StaffProvider;

class RyZerPlayerAuthListener implements Listener {

    /**
     * @param RyZerPlayerAuthEvent $event
     */
    public function auth(RyZerPlayerAuthEvent $event){
        $player = $event->getRyZerPlayer()->getPlayer();
        if(!$player->hasPermission("ryzer.login") && StaffProvider::loggedIn($player)) {
            StaffProvider::logout($player);
        }
    }
}