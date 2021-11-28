<?php

namespace ryzerbe\core\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\util\Settings;
use function in_array;

class PlayerJoinListener implements Listener {
    public function join(PlayerJoinEvent $event): void{
        $player = $event->getPlayer();
        if(!$player instanceof PMMPPlayer) return;
        if(!in_array($player->getAddress(), Settings::$ips)) {
            $player->kickFromProxy(TextFormat::RED."Please join about ryzer.be:19132");
            MainLogger::getLogger()->critical($event->getPlayer()->getName()." tried to join with address ".$event->getPlayer()->getAddress());
            return;
        }
        RyZerPlayerProvider::registerRyzerPlayer($player);
    }
}