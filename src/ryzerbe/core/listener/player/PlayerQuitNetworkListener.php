<?php

namespace ryzerbe\core\listener\player;

use BauboLP\Cloud\Events\PlayerQuitNetworkEvent;
use mysqli;
use pocketmine\event\Listener;
use ryzerbe\core\provider\PartyProvider;
use ryzerbe\core\util\async\AsyncExecutor;

class PlayerQuitNetworkListener implements Listener {

    /**
     * @param PlayerQuitNetworkEvent $event
     */
    public function quit(PlayerQuitNetworkEvent $event){
        $playerName = $event->getPlayerName();
       AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName): void{
           $party = PartyProvider::getPartyByPlayer($mysqli, $playerName);
           if($party === null) return;
           if($party === $playerName) {
               PartyProvider::deleteParty($mysqli, $playerName);
           }else {
               PartyProvider::leaveParty($mysqli, $playerName, $party);
           }

           $mysqli->query("UPDATE `playerdata` SET server='?' WHERE player='$playerName'");
       });
    }
}