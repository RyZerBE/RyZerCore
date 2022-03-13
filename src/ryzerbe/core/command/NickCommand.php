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
use ryzerbe\statssystem\provider\StatsAsyncProvider;


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
        if(!$this->testPermissionSilent($sender)) {
			$rbePlayer = $sender->getRyZerPlayer();
			if($rbePlayer->isNicked()) {
				$rbePlayer->unnick();
				return;
			}
        	$senderName = $sender->getName();
			StatsAsyncProvider::getTopEntriesOfColumn("Bedwars", "m_wins", function (array $topEntries) use ($senderName, $sender): void{
				if(!$sender->isConnected()) return;
				if(!isset($topEntries[$senderName])) {
					$sender->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("no-nick-perms", $senderName));
					return;
				}

				$rbePlayer = $sender->getRyZerPlayer();
				$rbePlayer->toggleNick();
			}, 3);
        	return;
		}

        if($sender->hasPermission("ryzer.nick.list") && isset($args[0])) {
            switch($args[0]) {
                case "list":
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
                    break;
                case "convertskins":
                    NickProvider::convertSkinsToSkinDB();
                    break;
            }

            return;
        }

        $rbePlayer = $sender->getRyZerPlayer();
        $rbePlayer->toggleNick();
    }
}