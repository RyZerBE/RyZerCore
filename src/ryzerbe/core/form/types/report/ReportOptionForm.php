<?php

namespace ryzerbe\core\form\types\report;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerMessagePacket;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\provider\CoinProvider;
use ryzerbe\core\provider\ReportProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use function implode;

class ReportOptionForm {

    /**
     * @param Player $player
     * @param array $report
     */
    public static function onOpen(Player $player, array $report){
        $form = new SimpleForm(function(Player $player, $data) use ($report): void{
            if($data === null) return;

            switch($data) {
                case "edit":
                    $playerName = $player->getName();
                    AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(\mysqli $mysqli) use ($report, $playerName): void{
                        $mysqli->query("UPDATE reports SET staff='$playerName' WHERE bad_player='".$report["bad_player"]."' AND state!='".ReportProvider::PROCESSED."'");
                        ReportProvider::setState($mysqli, ReportProvider::PROCESS, $report["bad_player"]);
                    }, function(Server $server, $result) use ($report, $player): void{
                        if(!$player->isConnected()) return;
                        CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "jumpto ".$report["bad_player"]);
                        $pk = new PlayerMessagePacket();
                        $pk->addData("players", $report["created_by"]);
                        $pk->addData("message", "&9Report &r&7Dein Report &6".$report["bad_player"]."&7 wird bearbeitet..");
                        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
                        StaffProvider::sendMessageToStaffs(ReportProvider::PREFIX.TextFormat::GOLD.$player->getName().TextFormat::GRAY." bearbeitet den Report ".TextFormat::GOLD.$report["bad_player"], true);
                    });
                    break;
                case "accept":
                    AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(\mysqli $mysqli) use ($report): void{
                        ReportProvider::setResult($mysqli, ReportProvider::ACCEPTED, $report["bad_player"]);
                        ReportProvider::setState($mysqli, ReportProvider::PROCESSED, $report["bad_player"]);
                    }, function(Server $server, $result) use ($report, $player): void{
                        if(!$player->isConnected()) return;
                        $pk = new PlayerMessagePacket();
                        $pk->addData("players", $report["created_by"]);
                        $pk->addData("message", "&9Report &r&7Dein Report &6".$report["bad_player"]."&7 wurde &aangenommen");
                        CoinProvider::addCoins($report["created_by"], 50);
                        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
                        StaffProvider::sendMessageToStaffs(ReportProvider::PREFIX.TextFormat::GOLD.$player->getName().TextFormat::GRAY." hat den Report ".TextFormat::GOLD.$report["bad_player"].TextFormat::GREEN." angenommen", true);
                    });
                    break;
                case "reject":
                    AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(\mysqli $mysqli) use ($report): void{
                        ReportProvider::setResult($mysqli, ReportProvider::REJECTED, $report["bad_player"]);
                        ReportProvider::setState($mysqli, ReportProvider::PROCESSED, $report["bad_player"]);
                    }, function(Server $server, $result) use ($report, $player): void{
                        if(!$player->isConnected()) return;
                        $pk = new PlayerMessagePacket();
                        $pk->addData("players", $report["created_by"]);
                        $pk->addData("message", "&9Report &r&7Dein Report &6".$report["bad_player"]."&7 wurde &cabgelehnt");
                        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
                        StaffProvider::sendMessageToStaffs(ReportProvider::PREFIX.TextFormat::GOLD.$player->getName().TextFormat::GRAY." hat den Report ".TextFormat::GOLD.$report["bad_player"].TextFormat::RED." abgelehnt", true);
                    });
                    break;
                case "abort":
                    AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(\mysqli $mysqli) use ($report): void{
                        ReportProvider::setState($mysqli, ReportProvider::OPEN, $report["bad_player"]);
                    }, function(Server $server, $result) use ($report, $player): void{
                        if(!$player->isConnected()) return;
                        StaffProvider::sendMessageToStaffs(ReportProvider::PREFIX.TextFormat::GOLD.$player->getName().TextFormat::GRAY." hat den Report ".TextFormat::GOLD.$report["bad_player"].TextFormat::YELLOW." freigegeben", true);
                    });
                    break;
                case "jump":
                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "jumpto ".$report["bad_player"]);
                    break;
            }
        });

        $content = [];
        $content[] = TextFormat::GOLD."Spieler: ".TextFormat::WHITE.$report["bad_player"];
        $content[] = TextFormat::GOLD."Grund: ".TextFormat::WHITE.$report["reason"];
        $content[] = TextFormat::GOLD."Gemeldet von: ".TextFormat::WHITE.$report["created_by"];
        $content[] = TextFormat::GOLD."Nick: ".$report["nick"];
        $content[] = TextFormat::GOLD."Notiz: ".TextFormat::WHITE.$report["notice"];
        $form->setContent(implode("\n", $content));
        if($report["state"] == ReportProvider::OPEN) {
            $form->addButton(TextFormat::YELLOW."Bearbeiten", 1, "https://media.discordapp.net/attachments/602115215307309066/909759389257310218/1933961.png?width=410&height=410", "edit");
        }else if($report["state"] == ReportProvider::PROCESS){
            if($report["staff"] === $player->getName()){
                $form->addButton(TextFormat::GREEN."Report annehmen", 0, "textures/ui/confirm.png", "accept");
                $form->addButton(TextFormat::RED."Report ablehnen", 0, "textures/ui/realms_red_x.png", "reject");
                $form->addButton(TextFormat::YELLOW."Report abgeben", 1, "https://media.discordapp.net/attachments/602115215307309066/909759389257310218/1933961.png?width=410&height=410", "abort");
            }else{
                $form->setContent(TextFormat::RED."Der Report wird gerade von ".TextFormat::GOLD.$report["staff"].TextFormat::RED." bearbeitet!");
                $form->addButton(TextFormat::YELLOW."Springe hinterher", 1, "https://media.discordapp.net/attachments/602115215307309066/907982544081924106/jump.png?width=410&height=410", "jump");
            }
        }

        $form->sendToPlayer($player);
    }
}