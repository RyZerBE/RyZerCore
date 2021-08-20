<?php


namespace baubolp\core\provider;


use baubolp\core\player\RyzerPlayer;
use baubolp\core\player\RyzerPlayerProvider;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\Player;
use pocketmine\Server;

class VanishProvider
{
    /** @var array  */
    public static array $vanishedPlayer = [];

    /**
     * @param RyzerPlayer $player
     * @param bool $vanish
     */
    public static function vanishPlayer(RyzerPlayer $player, bool $vanish)
    {
        if($vanish) {
            if(!in_array($player->getPlayer()->getName(), self::$vanishedPlayer)) {
                self::$vanishedPlayer[] = $player->getPlayer()->getName();
                self::removePlayerFromTab($player->getPlayer());
            }

            foreach (RyzerPlayerProvider::getRyzerPlayers() as $ryzerPlayer) {
                if(RankProvider::getRankJoinPower($ryzerPlayer->getRank()) > RankProvider::getRankJoinPower($player->getRank())) continue;

                $player->getPlayer()->despawnFrom($ryzerPlayer->getPlayer());
            }
        } else {
            self::addPlayerToTab($player->getPlayer());
            $player->getPlayer()->spawnToAll();
            unset(self::$vanishedPlayer[array_search($player->getPlayer()->getName(), self::$vanishedPlayer)]);
        }
    }

    /**
     * @param string $playerName
     * @return bool
     */
    public static function isVanished(string $playerName): bool
    {
        return in_array($playerName, self::$vanishedPlayer);
    }

    /**
     * @param Player $player
     */
    public static function removePlayerFromTab(Player $player): void
    {
        $entry = new PlayerListEntry();
        $pk = new PlayerListPacket();
        $uuid = $player->getUniqueId();
        $entry->uuid = $uuid;
        $pk->entries[] = $entry;
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        foreach(Server::getInstance()->getOnlinePlayers() as $players) {
            $players->sendDataPacket($pk);
        }
    }

    /**
     * @param Player $player
     */
    public static function addPlayerToTab(Player $player): void
    {
        $player->setNameTagVisible(TRUE);
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
}