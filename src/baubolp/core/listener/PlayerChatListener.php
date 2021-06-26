<?php


namespace baubolp\core\listener;


use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\ChatModProvider;
use baubolp\core\provider\DiscordProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\ModerationProvider;
use baubolp\core\provider\RankProvider;
use baubolp\core\Ryzer;
use baubolp\core\util\Emojis;
use baubolp\core\util\Webhooks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;

class PlayerChatListener implements Listener
{

    public function onChat(PlayerChatEvent $event)
    {
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($event->getPlayer()->getName()))) {
            if($obj->isMuted()) {
                $event->setCancelled();
                $muteData = $obj->getMuteData();
                $reason = $muteData['reason'];
                $duration = $muteData['duration'];
                $id = $muteData['id'];
                if($duration != "Permanent") {
                    if($obj->getLanguage() == "Deutsch") {
                        $muted = TextFormat::RED."Du wurdest vom Chat ".TextFormat::AQUA."AUSGESCHLOSSEN".TextFormat::RED."!"."\n"
                            .TextFormat::RED."Grund: ".TextFormat::AQUA.$reason."  ".TextFormat::RED."ID: ".$id."\n".
                            TextFormat::RED."Ende deines Mutes: ".TextFormat::AQUA.ModerationProvider::formatGermanDate($duration);
                    }else {
                        $muted = TextFormat::RED."You were ".TextFormat::AQUA."SUSPENDED".TextFormat::RED." from the chat!"."\n"
                            .TextFormat::RED."Reason: ".TextFormat::AQUA.$reason."  ".TextFormat::RED."ID: ".TextFormat::AQUA.$id."\n".
                            TextFormat::RED."Until: ".TextFormat::AQUA.$duration;
                    }
                    $event->getPlayer()->sendMessage($muted);
                }else {
                    if($obj->getLanguage() == "Deutsch") {
                        $muted = TextFormat::RED."Du wurdest vom Chat ".TextFormat::AQUA."AUSGESCHLOSSEN".TextFormat::RED."!"."\n"
                            .TextFormat::RED."Grund: ".TextFormat::AQUA.$reason."  ".TextFormat::RED."ID: ".$id."\n".
                            TextFormat::RED."Ende deines Mutes: ".TextFormat::AQUA."PERMANENT";
                    }else {
                        $muted = TextFormat::RED."You were ".TextFormat::AQUA."SUSPENDED".TextFormat::RED." from the chat!"."\n"
                            .TextFormat::RED."Reason: ".TextFormat::AQUA.$reason."  ".TextFormat::RED."ID: ".$id."\n".
                            TextFormat::RED."Until: ".TextFormat::AQUA."PERMANENT";
                    }
                    $event->getPlayer()->sendMessage($muted);
                }
                return;
            }

            if(ChatModProvider::checkCaps($event->getMessage()) && !$event->getPlayer()->hasPermission("chatmod.bypass.spam")) {
                $obj->setLastMessage(strtolower($event->getMessage()));
                $badWord = ChatModProvider::isBadWord($event->getMessage());
                if(is_bool($badWord)) {
                    if($badWord) {
                        $event->setCancelled();
                        $event->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('chatmod-detect-badword', $event->getPlayer()->getName()));
                        return;
                    }else {
                        $event->setMessage(strtolower($event->getMessage()));
                    }
                }else {
                    $event->setMessage(strtolower($event->getMessage()));
                }
                //$event->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('chatmod-detect-caps', $event->getPlayer()->getName()));
            }else if(ChatModProvider::equalsLastMessageWithText($obj, $event->getMessage()) && !$event->getPlayer()->hasPermission("chatmod.bypass.lastmessage")) {
                $event->setCancelled();
                $event->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('chatmod-detect-equals-lastmessage', $event->getPlayer()->getName()));
                return;
            }else if(ChatModProvider::isUrl($event->getMessage()) && !$event->getPlayer()->hasPermission("chatmod.bypass.url")) {
                $event->setCancelled();
                $event->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('chatmod-detect-url', $event->getPlayer()->getName()));
            }else if(ChatModProvider::isIp($event->getMessage())) {
                $event->setCancelled();
                $event->getPlayer()->sendMessage(Ryzer::PREFIX . LanguageProvider::getMessageContainer('chatmod-detect-ip', $event->getPlayer()->getName()));
                return;
            }else if(ChatModProvider::mustWait($event->getPlayer()->getName()) && !$event->getPlayer()->hasPermission("chatmod.bypass.spam")) {
                $event->setCancelled();
                $event->getPlayer()->sendMessage(Ryzer::PREFIX . LanguageProvider::getMessageContainer('chatmod-detect-spam', $event->getPlayer()->getName()));
                return;
            }else {
                ChatModProvider::addWaiter($event->getPlayer()->getName());
                $obj->setLastMessage($event->getMessage());
                $badWord = ChatModProvider::isBadWord($event->getMessage());
                if(is_bool($badWord)) {
                    if($badWord) {
                        $event->setCancelled();
                        $event->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('chatmod-detect-badword', $event->getPlayer()->getName()));
                        return;
                    }
                }else {
                    $event->setMessage($badWord);
                }
            }

            DiscordProvider::sendMessageToDiscord("Spion", $event->getPlayer()->getName()."[".CloudProvider::getServer()."] | ".str_replace("@", "", $event->getMessage()), Webhooks::CHATLOG);
            $event->setFormat(RankProvider::returnChatPrefix($obj, $event->getMessage()));
        }
    }

}