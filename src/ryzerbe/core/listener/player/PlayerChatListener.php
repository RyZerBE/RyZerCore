<?php

namespace ryzerbe\core\listener\player;

use BauboLP\Cloud\Provider\CloudProvider;
use DateTime;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\WebhookLinks;
use function rand;
use function str_replace;

class PlayerChatListener implements Listener {
    public function chat(PlayerChatEvent $event): void{
        $player = $event->getPlayer();
        $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($rbePlayer === null) return;

        if($rbePlayer->getMuteTime() !== null){
            if($rbePlayer->getMuteTime() > new DateTime("now")){
                $event->setCancelled();
                $player->sendMessage(LanguageProvider::getMessageContainer("mute-screen", $player->getName(), ["#reason" => $rbePlayer->getMuteReason(), "#until" => str_replace("&", TextFormat::ESCAPE, PunishmentProvider::getUntilFormat($rbePlayer->getMuteTime()->format("Y-m-d H:i:s"))), "#id" => $rbePlayer->getMuteId()]));
            }
            return;
        }

        $level = ($rbePlayer->getNickInfo() !== null) ? $rbePlayer->getNickInfo()->getLevel() : $rbePlayer->getNetworkLevel()->getLevel();
        $levelColor = $rbePlayer->getNetworkLevel()->getLevelColor($level);
        $clan = ($rbePlayer->getClan() !== null && $rbePlayer->getNickInfo() === null) ? $rbePlayer->getClan()->getClanTag() : "";
        $event->setFormat($levelColor.$level.TextFormat::GRAY." | ".TextFormat::RESET.str_replace("{player_name}", $rbePlayer->getName(true).TextFormat::DARK_GRAY." [".$clan.TextFormat::DARK_GRAY."]", str_replace("{MSG}", $event->getMessage(), ($rbePlayer->getNick() !== null) ? RankManager::getInstance()->getBackupRank()->getChatPrefix() : $rbePlayer->getRank()->getChatPrefix())));
        $discordMessage = new DiscordMessage(WebhookLinks::CHAT_LOG);
        $discordMessage->setMessage($player->getName()."[".CloudProvider::getServer()."] ".str_replace("@", "", $event->getMessage()));
        $discordMessage->send();
    }
}