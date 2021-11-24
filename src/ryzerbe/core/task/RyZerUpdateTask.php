<?php

namespace ryzerbe\core\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use ryzerbe\core\anticheat\AntiCheatManager;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\provider\VanishProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\animation\AnimationManager;
use ryzerbe\core\util\Settings;
use ryzerbe\core\util\TaskUtils;
use function array_rand;

class RyZerUpdateTask extends Task {
    public function onRun(int $currentTick){
        Server::getInstance()->getNetwork()->unblockAddress("5.181.151.61");

        if(($currentTick % 20) === 0){
            foreach(VanishProvider::$vanishedPlayer as $playerName){
                if(($player = Server::getInstance()->getPlayerExact($playerName)) !== null){
                    if(($ryzerPlayer = RyzerPlayerProvider::getRyzerPlayer($player->getName())) !== null){
                        VanishProvider::vanishPlayer($ryzerPlayer, true);
                    }
                }
            }

            foreach(RyZerPlayerProvider::getRyzerPlayers() as $ryzerPlayer){
                $ryzerPlayer->gameTimeTicks++;
            }
        }

        if($currentTick % TaskUtils::minutesToTicks(3) === 0){
            $autoMessage = Settings::$autoMessages[array_rand(Settings::$autoMessages)];
            foreach(Server::getInstance()->getOnlinePlayers() as $player){
                $player->sendMessage("\n\n\n\n".RyZerBE::PREFIX.LanguageProvider::getMessageContainer($autoMessage, $player)."\n");
            }
        }

        if($currentTick % TaskUtils::minutesToTicks(1) === 0){
            StaffProvider::refresh();
        }

        foreach(AnimationManager::getInstance()->getActiveAnimations() as $animation) {
            $animation->tick();
        }
        AntiCheatManager::getInstance()->onUpdate($currentTick);
    }
}