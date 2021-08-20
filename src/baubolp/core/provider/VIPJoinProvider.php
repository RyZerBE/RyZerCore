<?php


namespace baubolp\core\provider;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use pocketmine\Player;
use pocketmine\Server;

class VIPJoinProvider
{
    /** @var bool  */
    private static bool $vipJoin = false;
    /** @var boolean */
    private static bool $stopCheck = true;
    /** @var int|null */
    private static ?int $players = null;

    /**
     * @return bool
     */
    public static function isVipJoin(): bool
    {
        return self::$vipJoin;
    }

    public static function activate(): void
    {
        self::$vipJoin = true;
    }

    public static function deactivate(): void
    {
        self::$vipJoin = false;
    }

    /**
     * @return int
     */
    public static function getPlayers(): int
    {
        if(self::$players == null)
            self::$players = Server::getInstance()->getMaxPlayers();

        return self::$players;
    }

    /**
     * @param int $players
     */
    public static function setPlayers(int $players): void
    {
        self::$players = $players;
    }

    public static function check(Player $joinedPlayer)
    {
        if(!self::isVipJoin() || self::$stopCheck) return;

        var_dump("Check...");
        $checked = 0;
        $players = 0;
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if($player->getName() != $joinedPlayer->getName())
                $players++;
        }
        if($players >= self::getPlayers()) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
               if($player->getName() != $joinedPlayer->getName()) {
                   if(($obj = RyzerPlayerProvider::getRyzerPlayer($player->getName())) != null && ($joinedObj = RyzerPlayerProvider::getRyzerPlayer($joinedPlayer->getName())) != null) {
                       if(RankProvider::getRankJoinPower($obj->getRank()) < RankProvider::getRankJoinPower($joinedObj->getRank())) {
                           $player->kick(LanguageProvider::getMessageContainer('vip-kick', $player->getName(), ['#rank' => $joinedObj->getRank()]), false);
                           $joinedPlayer->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('vip-kick-successful', $joinedPlayer->getName()));
                           var_dump($player->getName()." kicked!");
                           break;
                       }else {
                           $checked++;
                       }
                   }
               }
            }
            if($checked >= self::getPlayers()) {
                var_dump("No player can be kicked :/");
                $joinedPlayer->kick(LanguageProvider::getMessageContainer('vip-kick-failed', $joinedPlayer->getName()), false);
            }
        }
    }

    public static function stopChecks()
    {
        self::$stopCheck = true;
    }

    public static function activateChecks()
    {
        self::$stopCheck = false;
    }

    /**
     * @return bool
     */
    public static function stoppedCheck(): bool
    {
        return self::$stopCheck;
    }
}