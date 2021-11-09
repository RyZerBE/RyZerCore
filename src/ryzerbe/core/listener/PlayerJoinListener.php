<?php

namespace ryzerbe\core\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\util\Settings;
use function in_array;

class PlayerJoinListener implements Listener {

    /**
     * @param PlayerJoinEvent $event
     */
    public function join(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if(!$player instanceof PMMPPlayer) return;
        if(!in_array($player->getAddress(), Settings::$ips)) {
            $player->kickFromProxy(TextFormat::RED."Please join about ryzer.be:19132");
            MainLogger::getLogger()->critical($event->getPlayer()->getName()." tried to join with address ".$event->getPlayer()->getAddress());
        }
        RyZerPlayerProvider::registerRyzerPlayer($player);
    }
}