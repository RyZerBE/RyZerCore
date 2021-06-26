<?php


namespace baubolp\core\form;


use BauboLP\Cloud\Bungee\BungeeAPI;
use baubolp\core\listener\own\JoinMeCreateEvent;
use baubolp\core\provider\JoinMEProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class JoinMeForm extends MenuForm
{

    public function __construct(Player $player, array $joinMe)
    {
        $options = [];
        $servers = [];
        if($player->hasPermission("core.joinme") && !JoinMEProvider::existJoinMe($player->getName()) && !JoinMEProvider::isServerForbidden()) {
            $options[] = new MenuOption(TextFormat::GREEN."Create JoinMe\n".TextFormat::AQUA."Click to create");
            $servers[] = "create";
        }

        foreach ($joinMe as $playerName => $server) {
            if($server == null) {
                $options[] = new MenuOption(LanguageProvider::getMessageContainer("no-joinme-exist", $player->getName()));
                $servers[] = null;
            }else {
                $options[] = new MenuOption(TextFormat::GREEN.$playerName."\n".TextFormat::GOLD.$server);
                $servers[] = $server;
            }
        }

        parent::__construct(Ryzer::PREFIX."JoinMe", "", $options, function (Player $player, int $selectedOption) use ($servers): void{
              $server = $servers[$selectedOption];
              if($server == null) return;
              if($server == "create") {
                  if(JoinMEProvider::existJoinMe($player->getName())) {
                      $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('joinme-already-exist', $player->getName()));
                  }else {
                      $ev = new JoinMeCreateEvent($player);
                      $ev->call();
                      if($ev->isCancelled()) {
                          $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer("joinme-cancelled-reason", $player->getName(), ["#reason" => $ev->getReason()]));
                          return;
                      }
                      JoinMEProvider::createJoinMe($player);
                  }
                  return;
              }

              BungeeAPI::transfer($player->getName(), $server);
        });
    }
}