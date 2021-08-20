<?php


namespace baubolp\core\listener;


use BauboLP\Cloud\Bungee\BungeeAPI;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\VIPJoinProvider;
use baubolp\core\Ryzer;
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

class PlayerJoinListener implements Listener
{

    private array $ips = ['5.181.151.61', '127.0.0.1'];

    public function playerJoin(PlayerJoinEvent $event)
    {
        if(in_array($event->getPlayer()->getAddress(), $this->ips)) {
            $correct_size = Skin::ACCEPTED_SKIN_SIZES[2];
            if (strlen($event->getPlayer()->getSkin()->getSkinData()) > $correct_size){
                $event->getPlayer()->setSkin(Ryzer::$backupSkin);
                $event->getPlayer()->sendMessage(Ryzer::PREFIX.TextFormat::RED."Your skin is not allowed on our server!");
            }

            RyzerPlayerProvider::registerRyzerPlayer($event->getPlayer());
        }else {
            BungeeAPI::kickPlayer($event->getPlayer()->getName(), "Please join about ryzer.be:19132");
            MainLogger::getLogger()->critical($event->getPlayer()->getName()." tried to join with address ".$event->getPlayer()->getAddress());
        }
    }

    public function playerLogin(PlayerLoginEvent $event)
    {
        if(VIPJoinProvider::getPlayers() >= count(Server::getInstance()->getOnlinePlayers()) && !VIPJoinProvider::isVipJoin() && !VIPJoinProvider::stoppedCheck()) {
            $event->getPlayer()->kick(LanguageProvider::getMessageContainer('vip-kick-off', "BauboLPYT"), false);
        }
    }
}