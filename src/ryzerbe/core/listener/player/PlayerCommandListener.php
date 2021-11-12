<?php

namespace ryzerbe\core\listener\player;

use BauboLP\Cloud\Provider\CloudProvider;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\Server;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\WebhookLinks;
use function str_replace;

class PlayerCommandListener implements Listener {
    public function onCommandExec(PlayerCommandPreprocessEvent $event): void{
        $command = $event->getMessage();
        $player = $event->getPlayer();
        if($command[0] == "/"){
            $discordMessage = new DiscordMessage(WebhookLinks::COMMAND_LOG);
            $discordMessage->setMessage($player->getName()."[".CloudProvider::getServer()."] ".$command);
            $discordMessage->send();
            $data = explode(" ", $command);
            if(Server::getInstance()->getCommandMap()->getCommand(str_replace(["//", "/", "#"], ["#", "", "/"], $data[0])) == null){
                $event->getPlayer()->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('command-not-found', $event->getPlayer()->getName()));
                $event->setCancelled();
            }
        }
    }
}