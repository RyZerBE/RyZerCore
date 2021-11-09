<?php

namespace ryzerbe\core\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\Server;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\RyZerBE;

class PlayerCommandListener implements Listener {
    /**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onCommandExec(PlayerCommandPreprocessEvent $event){
        $command = $event->getMessage();
        if($command[0] == "/"){
            //todo: send in discord channel
            $data = explode(" ", $command);
            if(Server::getInstance()->getCommandMap()->getCommand(str_replace(["//", "/", "#"], ["#", "", "/"], $data[0])) == null){
                $event->getPlayer()->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('command-not-found', $event->getPlayer()->getName()));
                $event->setCancelled();
            }
        }
    }
}