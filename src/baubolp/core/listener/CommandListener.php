<?php


namespace baubolp\core\listener;


use baubolp\core\provider\DiscordProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use baubolp\core\util\Webhooks;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\level\ChunkLoader;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\Player;
use pocketmine\Server;

class CommandListener implements Listener
{

    public function onCommandExec(PlayerCommandPreprocessEvent $event)
    {
        $command = $event->getMessage();
        if ($command[0] == "/") {
            DiscordProvider::sendMessageToDiscord("Spion", $event->getPlayer()->getName()." | ".$command, Webhooks::COMMAND_LOG);
            $data = explode(" ", $command);
            if (Server::getInstance()->getCommandMap()->getCommand(str_replace(["//", "/", "#"], ["#", "", "/"], $data[0])) == null) {
                $event->getPlayer()->sendMessage(Ryzer::PREFIX . LanguageProvider::getMessageContainer('command-not-found', $event->getPlayer()->getName()));
                $event->setCancelled();
                return;
            }
        }
    }
}