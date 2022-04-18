<?php
declare(strict_types=1);
namespace ryzerbe\core\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use ryzerbe\core\player\PMMPPlayer;


class FriendCommand extends Command{

	public function __construct(){
		parent::__construct("friend", "friend main command", "", []);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof PMMPPlayer) return;

		if(empty($args[0])) {
			//TODO: Send help list
			return;
		}

		switch ($args[0]) {
			default:
				//TODO: Send help list
				break;
		}
	}
}
