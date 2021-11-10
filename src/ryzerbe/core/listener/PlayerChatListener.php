<?php

namespace ryzerbe\core\listener;

use DateTime;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\PunishmentProvider;
use function str_replace;

class PlayerChatListener implements Listener {


    public function chat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($rbePlayer === null) return;

        if($rbePlayer->getMuteTime() !== null){
            if($rbePlayer->getMuteTime() > new DateTime("now")){
                $event->setCancelled();
                $player->sendMessage(LanguageProvider::getMessageContainer("mute-screen", $player->getName(), ["#reason" => $rbePlayer->getMuteReason(), "#until" => PunishmentProvider::getUntilFormat($rbePlayer->getMuteTime()->format("Y-m-d H:i:s")), "#id" => $rbePlayer->getMuteId()]));
            }
            return;
        }

        $event->setFormat(str_replace("{player_name}", $player->getName(), str_replace("{MSG}", $event->getMessage(), $rbePlayer->getRank()->getChatPrefix())));
    }
}