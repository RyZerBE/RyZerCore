<?php


namespace baubolp\core\module\TrollSystem\forms;


use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use baubolp\core\module\TrollSystem\TrollSystem;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class TrollMenuForm extends MenuForm
{

    public function __construct()
    {
        $buttons = [
          "Vanish",
          "Change GameMode",
          "Freeze Player",
          "Crash Player",
          "Drop Items",
          "AntiDrop",
          "MLG Player",
          "Server lag",
          "TPALL",
          "Granates",
          "Alone",
          "AntiCheat Kick",
          "Cloud Error"
        ];
        $options = [];
        foreach ($buttons as $button) {
            $options[] = new MenuOption(TextFormat::RED.$button);
        }
        parent::__construct(TrollSystem::Prefix.TextFormat::YELLOW."Menu", "", $options, function (Player $player, int $selectedOption) use ($buttons):void{
            $button = $buttons[$selectedOption];
            switch ($button) {
                case "Server lag":
                    $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-server-lag', $player->getName(), ['#seconds' => 10]));
                    sleep(10);
                    break;
                case "Granates":
                    $item = Item::get(Item::EGG, 0, 16)->setCustomName(TextFormat::RED."Granate");
                    $player->getInventory()->addItem($item);
                    break;
                case "Vanish":
                    if(!in_array($player->getName(), Ryzer::getTrollSystem()->vanish)) {
                        $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-vanish', $player->getName()));
                        Ryzer::getTrollSystem()->vanish[] = $player->getName();
                        foreach (Server::getInstance()->getOnlinePlayers() as $oPlayers) {
                            $oPlayers->hidePlayer($player);
                        }
                    }else {
                        $player->sendMessage(TrollSystem::Prefix . LanguageProvider::getMessageContainer('troll-no-longer-vanish', $player->getName()));
                        unset(Ryzer::getTrollSystem()->vanish[array_search($player->getName(), Ryzer::getTrollSystem()->vanish)]);
                        foreach (Server::getInstance()->getOnlinePlayers() as $oPlayers) {
                            $oPlayers->showPlayer($player);
                        }
                    }
                    break;
                case "Change GameMode":
                    $player->sendForm(new SelectGameModeForm());
                    break;
                case "TPALL":
                    foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                        $players->teleport($player);
                    }
                    break;
                default:
                    $player->sendForm(new SelectPlayerForm($button));
                    break;
            }
        });
    }
}