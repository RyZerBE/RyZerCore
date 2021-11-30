<?php

namespace ryzerbe\core\form\types\rank;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use function explode;
use function implode;

class RankMainForm {
    public static function onOpen(Player $player, array $extraData = []): void{
        $form = new SimpleForm(function(Player $player, $data): void{
            if($data === null) return;
            switch($data){
                case "create":
                    RankCreateForm::onOpen($player);
                    break;
                case "ranks":
                    RankOverviewForm::onOpen($player);
                    break;
                case "player_perm":
                    $options = [
                        "ADD",
                        "REMOVE"
                    ];
                    $form = new CustomForm(function(Player $player, $data) use ($options): void{
                        if($data === null) return;

                        $option = $options[$data["option"]];
                        $permission = $data["perm"];
                        $playerName = $data["player"];

                        switch($option) {
                            case "ADD":
                                $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($playerName);
                                if($rbePlayer !== null) {
                                    $rbePlayer->addPlayerPermission($permission, true, true);
                                    $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Spieler ".TextFormat::GOLD.$playerName.TextFormat::GRAY." hat die Permission ".TextFormat::GOLD.$permission.TextFormat::GREEN." erhalten.");
                                    return;
                                }

                                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use($permission, $playerName): void{
                                    $res = $mysqli->query("SELECT permissions FROM playerranks WHERE player='$playerName'");
                                    if($res->num_rows <= 0) return;
                                    if($data = $res->fetch_assoc()){
                                        $data = $data["permissions"];
                                        $permissions = explode(";", $data["permissions"]);
                                        $permissions[] = $permission;
                                        $permissions = implode(";", $permissions);
                                        $mysqli->query("UPDATE playerranks SET permissions='$permissions' WHERE player='$playerName'");
                                    }
                                }, function(Server $server, $result) use ($player, $playerName, $permission): void{
                                    if(!$player->isConnected()) return;
                                    $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Spieler ".TextFormat::GOLD.$playerName.TextFormat::GRAY." hat die Permission ".TextFormat::GOLD.$permission.TextFormat::GREEN." erhalten.");
                                });
                                break;
                            case "REMOVE":
                                $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($playerName);
                                if($rbePlayer !== null) {
                                    $rbePlayer->removePlayerPermission($permission, true, true);
                                    $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Spieler ".TextFormat::GOLD.$playerName.TextFormat::GRAY." wurde die Permission ".TextFormat::GOLD.$permission.TextFormat::RED." entfernt.");
                                    return;
                                }

                                AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use($permission, $playerName): void{
                                    $res = $mysqli->query("SELECT permissions FROM playerranks WHERE player='$playerName'");
                                    if($res->num_rows <= 0) return;
                                    if($data = $res->fetch_assoc()){
                                        $data = $data["permissions"];
                                        $permissions = explode(";", $data["permissions"]);
                                        $permissions[] = $permission;
                                        $permissions = implode(";", $permissions);
                                        $mysqli->query("UPDATE playerranks SET permissions='$permissions' WHERE player='$playerName'");
                                    }
                                }, function(Server $server, $result) use ($player, $playerName, $permission): void{
                                    if(!$player->isConnected()) return;
                                    $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Spieler ".TextFormat::GOLD.$playerName.TextFormat::GRAY." wurde die Permission ".TextFormat::GOLD.$permission.TextFormat::RED." entfernt.");
                                });
                                break;
                        }
                    });

                    $form->addInput(TextFormat::GREEN."Name of Player", "Chillihero", "", "player");
                    $form->addInput(TextFormat::GREEN."Permission", "pocketmine.command.gamemode", "", "perm");
                    $form->addDropdown(TextFormat::GREEN."Option", $options, null, "option");
                    $form->sendToPlayer($player);
                    break;
            }
        });
        $form->setTitle(TextFormat::RED . TextFormat::BOLD . "Ranks");
        $form->addButton(TextFormat::GREEN . "Create Rank", 1, "https://media.discordapp.net/attachments/602115215307309066/907559017243631636/218648.png?width=663&height=702", "create");
        $form->addButton(TextFormat::RED . "Ranks", 1, "https://media.discordapp.net/attachments/602115215307309066/907558166605230080/1805999.png?width=410&height=410", "ranks");
        $form->addButton(TextFormat::YELLOW."Player Permissions",1, "https://media.discordapp.net/attachments/602115215307309066/907567888418869248/2172839.png?width=410&height=410", "player_perm");
        $form->sendToPlayer($player);
    }
}