<?php

namespace ryzerbe\core\listener\player;

use mysqli;
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\provider\PlayerSkinProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\skin\SkinDatabase;
use function strlen;

class PlayerSkinChangeListener implements Listener {

    public function onSkinChange(PlayerChangeSkinEvent $event){
        $player = $event->getPlayer();
        if(!$player instanceof PMMPPlayer) return;
        $name = $player->getName();
        $newSkin = $event->getNewSkin();
        $skinData = $newSkin->getSkinData();
        $oldSkin = $event->getOldSkin();
        $geometryName = $newSkin->getGeometryName();
        if($oldSkin->getSkinData() === $skinData) return;
        if($player->hasDelay("skin_change")){
            $event->setCancelled();
            $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("skin-change-cancel-delay", $player));
            return;
        }

        $correct_size = Skin::ACCEPTED_SKIN_SIZES[2];
        if(strlen($skinData) > $correct_size) {
            SkinDatabase::getInstance()->loadSkin("steve", function(bool $success) use ($player): void{
                if(!$player->isConnected() || !$player instanceof PMMPPlayer) return;
                if(!$success) $player->kickFromProxy("&cSkin aren't allowed! Please switch your skin!");
                else $player->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Skin aren't allowed!");
            }, null, $player);
            return;
        }

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name, $skinData, $geometryName): void{
            PlayerSkinProvider::storeSkin($name, $skinData, $geometryName, $mysqli);
        });
    }
}