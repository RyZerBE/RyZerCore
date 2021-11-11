<?php

namespace ryzerbe\core\provider;

use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\Player;
use pocketmine\Server;
use ryzerbe\core\player\RyZerPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;

class VanishProvider implements RyZerProvider {
    public static array $vanishedPlayer = [];

    public static function vanishPlayer(RyZerPlayer $player, bool $vanish){
        if($vanish){
            if(!in_array($player->getPlayer()->getName(), self::$vanishedPlayer)){
                self::$vanishedPlayer[] = $player->getPlayer()->getName();
                self::removePlayerFromTab($player->getPlayer());
            }
            foreach(RyzerPlayerProvider::getRyzerPlayers() as $ryzerPlayer){
                if($ryzerPlayer->getRank()->getJoinPower() > $player->getRank()->getJoinPower()) continue;
                $player->getPlayer()->despawnFrom($ryzerPlayer->getPlayer());
            }
        }
        else{
            self::addPlayerToTab($player->getPlayer());
            $player->getPlayer()->spawnToAll();
            unset(self::$vanishedPlayer[array_search($player->getPlayer()->getName(), self::$vanishedPlayer)]);
        }
    }

    public static function removePlayerFromTab(Player $player): void{
        $entry = new PlayerListEntry();
        $pk = new PlayerListPacket();
        $uuid = $player->getUniqueId();
        $entry->uuid = $uuid;
        $pk->entries[] = $entry;
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        foreach(Server::getInstance()->getOnlinePlayers() as $players){
            $players->sendDataPacket($pk);
        }
    }

    public static function addPlayerToTab(Player $player): void{
        $player->setNameTagVisible(true);
        $entry = new PlayerListEntry();
        $entry->uuid = $player->getUniqueId();
        $entry->entityUniqueId = $player->getId();
        $entry->xboxUserId = $player->getXuid();
        $entry->username = $player->getName();
        $entry->skinData = SkinAdapterSingleton::get()->toSkinData($player->getSkin());
        $pk = new PlayerListPacket();
        $pk->entries[] = $entry;
        $pk->type = PlayerListPacket::TYPE_ADD;
        foreach(Server::getInstance()->getOnlinePlayers() as $players){
            $players->sendDataPacket($pk);
        }
    }

    public static function isVanished(string $playerName): bool{
        return in_array($playerName, self::$vanishedPlayer);
    }
}