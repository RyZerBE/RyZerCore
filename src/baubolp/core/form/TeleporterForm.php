<?php


namespace baubolp\core\form;


use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class TeleporterForm extends MenuForm
{

    public function __construct()
    {
        $options = [];
        $players = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if($player->getGamemode() != 3) {
                $players[] = $player->getName();
                $options[] = new MenuOption($player->getDisplayName());
            }
        }
        parent::__construct(Ryzer::PREFIX.TextFormat::YELLOW."Teleporter", "", $options, function (Player $player, int $selectedOption) use($players):void{
              $playerName = $players[$selectedOption];
              if(($selectedPlayer = Server::getInstance()->getPlayerExact($playerName)) != null) {
                  $player->teleport($selectedPlayer);
                  $player->sendMessage(Ryzer::PREFIX . LanguageProvider::getMessageContainer('teleport-to-player', $player->getName(), ['#playername' => $selectedPlayer->getDisplayName()]));
              }
        });
    }

}