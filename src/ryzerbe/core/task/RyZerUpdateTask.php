<?php

namespace ryzerbe\core\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\provider\VanishProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\Settings;
use function array_rand;

class RyZerUpdateTask extends Task {


    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick){
        Server::getInstance()->getNetwork()->unblockAddress("5.181.151.61");

        if(($currentTick % 20) === 0){
            foreach(VanishProvider::$vanishedPlayer as $playerName){
                if(($player = Server::getInstance()->getPlayerExact($playerName)) != null){
                    if(($ryzerPlayer = RyzerPlayerProvider::getRyzerPlayer($player->getName())) != null)
                        VanishProvider::vanishPlayer($ryzerPlayer, true);
                }
            }

            foreach(RyZerPlayerProvider::getRyzerPlayers() as $ryzerPlayer){
                $ryzerPlayer->gameTimeTicks++;
            }
        }

        if($currentTick % ((20 * 60) * 3) === 0){
            $autoMessage = Settings::$autoMessages[array_rand(Settings::$autoMessages)];
            foreach(Server::getInstance()->getOnlinePlayers() as $player){
                $player->sendMessage("\n\n\n\n".RyZerBE::PREFIX.LanguageProvider::getMessageContainer($autoMessage, $player)."\n");
            }
        }

        if($currentTick % (20 * 60) === 0){
            StaffProvider::refresh();
        }
    }
}