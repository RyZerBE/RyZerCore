<?php

namespace ryzerbe\core\command;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\entity\SkullEntity;
use ryzerbe\core\util\animation\AnimationManager;
use ryzerbe\core\util\animation\defaults\FireworkPlayerHeadWinnerAnimation;
use ryzerbe\core\util\SkinUtils;

class TestCommand extends Command {

    public function __construct(){
        parent::__construct("test", "player head test", "", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player) return;

        $form = new SimpleForm(function(Player $player, $data): void{});
        $form->addButton($sender->getName()." Head", 1, SkinUtils::getPlayerHeadIcon($sender->getName()));
        $form->sendToPlayer($sender);

        /*$pos = $sender->asPosition();
        $pos = new Position($pos->x, $pos->y + 3, $pos->z, $pos->getLevelNonNull());
        $headPosition = new Position($pos->x, $pos->y + 5, $pos->z, $pos->getLevelNonNull());
        $animation = new FireworkPlayerHeadWinnerAnimation([$pos], 10, $headPosition, $sender->getSkin(), function(SkullEntity $skullEntity) use ($sender): void{
            if(!$sender->isConnected()) return;
            $skullEntity->setNameTagAlwaysVisible();
            $skullEntity->setNameTag("Team ".$sender->getDisplayName().TextFormat::WHITE." won");
        });
        AnimationManager::getInstance()->addActiveAnimation($animation);*/
    }
}