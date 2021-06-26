<?php


namespace baubolp\core\command;


use baubolp\core\provider\RankProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class RyzerPermsCommand extends Command
{

    public function __construct()
    {
        parent::__construct('rperm', "rank- and permissionsystem", "", ['']);
        $this->setPermission("core.rperm");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender)) return;

        $helpList = "Hilfeliste zu RyzerPerms:\n".
                    "/rperms setrank <Player> <Rank>\n".
                    "/rperms removerankpermission <Rank> <Permission>\n".
                    "/rperms addrankpermission <Rank> <Permission>\n".
                    "/rperms addplayerpermission <Rank> <Permission>\n".
                    "/rperms removeplayerpermission <Player> <Permission>\n".
                    "/rperms setjoinpower <Rank> <JoinPower>\n".
                    "/rperms addrank <Rank>";
        if(empty($args[0])) {
            $sender->sendMessage(Ryzer::PREFIX.$helpList);
            return;
        }

        if(empty($args[1])) {
            $sender->sendMessage(Ryzer::PREFIX.$helpList);
            return;
        }

        switch ($args[0]) {
            case "setrank":
             if(empty($args[2])) {
                 $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/rperm setrank <Player> <Rank>");
                 return;
             }
            $playerName = $args[1];
            $rank = $args[2];
            if(!RankProvider::existRank($rank)) {
                $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Verfügbare Ränge: ".TextFormat::AQUA.implode(", ", array_keys(Ryzer::$ranks)));
                return;
            }

            RankProvider::setRank($playerName, $sender->getName(), $rank);
            break;
            case "removerankpermission":
                if(empty($args[2])) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/rperm removerankpermission <Rank> <Permission>");
                    return;
                }
                $rank = $args[1];
                $permission = $args[2];
                if(!RankProvider::existRank($rank)) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Verfügbare Ränge: ".TextFormat::AQUA.implode(", ", array_keys(Ryzer::$ranks)));
                    return;
                }

                RankProvider::removePermFromRank($rank, $sender->getName(), $permission);
                break;
            case "addrankpermission":
                if(empty($args[2])) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/rperm addrankpermission <Rank> <Permission>");
                    return;
                }
                $rank = $args[1];
                $permission = $args[2];
                if(!RankProvider::existRank($rank)) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Verfügbare Ränge: ".TextFormat::AQUA.implode(", ", array_keys(Ryzer::$ranks)));
                    return;
                }

                RankProvider::addPermToRank($rank, $sender->getName(), $permission);
                break;
            case "removeplayerpermission":
                if(empty($args[2])) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/rperm removeplayerpermission <Player> <Permission>");
                    return;
                }
                $playerName = $args[1];
                $permission = $args[2];

                RankProvider::removePlayerPermission($playerName, $sender->getName(), $permission);
                break;
            case "addplayerpermission":
                if(empty($args[2])) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/rperm addplayerpermission <Player> <Permission>");
                    return;
                }
                $playerName = $args[1];
                $permission = $args[2];

                RankProvider::addPermToPlayer($playerName, $sender->getName(), $permission);
                break;
            case "setjoinpower":
                if(empty($args[2])) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/rperm setjoinpower <Rank> <JoinPower>");
                    return;
                }
                $rank = $args[1];
                $joinPower = $args[2];
                if(!RankProvider::existRank($rank)) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Verfügbare Ränge: ".TextFormat::AQUA.implode(", ", array_keys(Ryzer::$ranks)));
                    return;
                }

                RankProvider::setJoinPower($rank, $sender->getName(), $joinPower);
                break;
            case "addrank":
                $rank = $args[1];
                if(RankProvider::existRank($rank)) {
                    $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."Der Rang existiert bereits!");
                    return;
                }

                RankProvider::createRank($rank, 0);
                break;
        }
    }
}