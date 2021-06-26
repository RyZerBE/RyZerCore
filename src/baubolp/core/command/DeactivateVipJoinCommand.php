<?php


namespace baubolp\core\command;


use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\provider\VIPJoinProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class DeactivateVipJoinCommand extends Command
{

    public function __construct()
    {
        parent::__construct("disablevipjoin", "", "", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if($sender->hasPermission("core.disablevipjoin") || CloudBridge::getCloudProvider()->isServerPrivate(CloudProvider::getServer()) == $sender->getName()) {
            VIPJoinProvider::deactivate();
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."VIP join disabled!");
        }else {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
        }
    }
}