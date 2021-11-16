<?php

namespace ryzerbe\core\command;

use BauboLP\Cloud\Provider\CloudProvider;
use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\provider\NickProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;

class NickCommand extends Command {

    public function __construct(){
        parent::__construct("nick", "hide your identity", "", []);
        $this->setPermission("ryzer.nick");
        $this->setPermissionMessage(RyZerBE::PREFIX.TextFormat::RED."No Permissions! :l");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof PMMPPlayer) return;

        if($sender->hasPermission("ryzer.nick.list") && isset($args[0])) {
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli): array{
                return NickProvider::getActiveNicks($mysqli);
            }, function(Server $server, array $nicks) use ($sender): void{
                if(!$sender->isConnected()) return;

                $form = new SimpleForm(function(Player $player, $data): void{});
                $form->setTitle(TextFormat::GOLD."List of active nicks");
                foreach($nicks as $playerName => $nick) {
                    $form->addButton(TextFormat::GREEN.$nick."\n".TextFormat::DARK_GRAY."(".TextFormat::GOLD.$playerName.TextFormat::DARK_GRAY.")");
                }

                $form->sendToPlayer($sender);
            });
            return;
        }

        if(stripos(CloudProvider::getServer(), "CWBW") !== false) {
            $sender->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer('cannot-nick-in-cwbw', $sender));
            return;
        }
        $rbePlayer = $sender->getRyZerPlayer();
        if($rbePlayer->getNick() === null) NickProvider::nick($sender);else NickProvider::unnick($sender);
    }
}