<?php

namespace ryzerbe\core\form\types\clan;

use BauboLP\Cloud\CloudBridge;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\form\types\ConfirmationForm;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\Clan;

class ClanMainForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData = []){
        $form = new SimpleForm(function(Player $player, $data) use ($extraData): void{
            if($data === null) return;

            $playerName = $player->getName();

            switch($data){
                case "create_clan":
                    CreateClanForm::open($player);
                    break;
                case "requests":
                    ClanRequestsForm::open($player, $extraData);
                    break;
                case "search_clan":
                    SearchClanForm::open($player);
                    break;
                case "top_clans":
                    $top = 10;
                    AsyncExecutor::submitMySQLAsyncTask("BetterClans", function(\mysqli $mysqli) use ($top): array{
                        $topList = [];
                        $result = $mysqli->query("SELECT clan_name, elo FROM Clans ORDER BY elo DESC LIMIT $top");
                        if($result->num_rows > 0){
                            while($data = $result->fetch_assoc()){
                                $topList[$data["clan_name"]] = $data["elo"];
                            }
                        }
                        return $topList;
                    }, function(Server $server, $top) use ($playerName): void{
                        if(($player = $server->getPlayer($playerName)) != null){
                            TopClansForm::open($player, ["top" => $top]);
                        }
                    });
                    break;
                case "clan_info":
                    #CloudBridge::getCloudProvider()->dispatchProxyCommand($playerName, "clan info");
                    ClanInformationForm::open($player, $extraData);
                    break;
                case "leave":
                    ConfirmationForm::onOpen($player, LanguageProvider::getMessageContainer("clan-leave-confirm", $playerName, ["#clanName" => $extraData["clanName"] ?? "???"]),
                        function(Player $player){
                            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan leave");
                        });
                    break;
                case "invite":
                    InvitePlayerForm::open($player);
                    break;
                case "kick":
                    $extraData["kick"] = TRUE;
                    KickClanMemberForm::open($player, $extraData);
                    break;
                case "role_update":
                    $extraData["role"] = TRUE;
                    KickClanMemberForm::open($player, $extraData);
                    break;
                case "color":
                    ClanTagColorForm::open($player, $extraData);
                    break;
                case "state":
                    ClanStateForm::open($player);
                    break;
                case "display_info":
                    ClanDisplayMessageForm::open($player, $extraData);
                    break;
                case "delete":
                    ConfirmationForm::onOpen($player, LanguageProvider::getMessageContainer("clan-delete-confirm", $playerName, ["#clanName" => $extraData["clanName"] ?? "???"]),
                        function(Player $player){
                            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan delete");
                        });                    break;
                case "cw":
                    JoinLeaveQueueOptionForm::open($player, $extraData);
                    break;
                case "cw_history":
                    CWHistoryForm::open($player, $extraData);
                    break;
            }
        });
        if(isset($extraData["clanName"])){//IS IN CLAN
            $openingState = match ((int)$extraData["status"]) {
                Clan::CLOSE => "https://media.discordapp.net/attachments/412217468287713282/880905980953649222/2949701.png?width=410&height=410",
                Clan::INVITE => "https://media.discordapp.net/attachments/412217468287713282/880906100579389490/invite-and-earn-1817171-1538039.png?width=205&height=205",
                Clan::OPEN => "https://media.discordapp.net/attachments/412217468287713282/880905482829725776/176080.png?width=410&height=410",
                default => "???"
            };
            switch($extraData["role"]){
                case "Member": //MEMBER
                    $form->addButton(TextFormat::YELLOW."Clan Info", 1, "https://media.discordapp.net/attachments/412217468287713282/880559672472535120/info.png?width=402&height=402", "clan_info");
                    $form->addButton(TextFormat::GREEN."ClanWar History", 1, "https://media.discordapp.net/attachments/412217468287713282/885513348018470962/1800196.png?width=410&height=410", "cw_history");
                    $form->addButton(TextFormat::GOLD."TOP 10", 1, "https://media.discordapp.net/attachments/412217468287713282/880530145331519538/954-9547395_file-emoji-u1f3c5-svg-wikimedia-commons-3rd-place.png?width=275&height=402", "top_clans");
                    $form->addButton(TextFormat::RED."Leave Clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880541899390324786/leave_clan.png?width=205&height=205", "leave");
                    break;
                case "Moderator"://MODERATOR
                    $form->addButton(TextFormat::DARK_AQUA."ClanWar", 1, "https://media.discordapp.net/attachments/412217468287713282/880547998449418351/swords-312440_960_720.png?width=402&height=402", "cw");
                    $form->addButton(TextFormat::YELLOW."Clan Info", 1, "https://media.discordapp.net/attachments/412217468287713282/880559672472535120/info.png?width=402&height=402", "clan_info");
                    $form->addButton(TextFormat::GREEN."ClanWar History", 1, "https://media.discordapp.net/attachments/412217468287713282/885513348018470962/1800196.png?width=410&height=410", "cw_history");
                    $form->addButton(TextFormat::GREEN."Co".TextFormat::AQUA."l".TextFormat::GOLD."or", 1, "https://media.discordapp.net/attachments/412217468287713282/880895769622753380/551221.png?width=402&height=402", "color");
                    $form->addButton(TextFormat::DARK_PURPLE."Display information", 1, "https://media.discordapp.net/attachments/412217468287713282/880896093041348628/3076404.png?width=402&height=402", "display_info");
                    $form->addButton(TextFormat::GOLD."TOP 10", 1, "https://media.discordapp.net/attachments/412217468287713282/880530145331519538/954-9547395_file-emoji-u1f3c5-svg-wikimedia-commons-3rd-place.png?width=275&height=402", "top_clans");
                    $form->addButton(TextFormat::AQUA."Invite player", 1, "https://media.discordapp.net/attachments/412217468287713282/880558165022879775/invite.png?width=402&height=402", "invite");
                    $form->addButton(TextFormat::RED."Kick Player", 1, "https://media.discordapp.net/attachments/412217468287713282/880544897587896381/user-interface-remove-friend-block-kick-glyph-512.png?width=402&height=402", "kick");
                    $form->addButton(TextFormat::RED."Leave Clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880541899390324786/leave_clan.png?width=205&height=205", "leave");
                    break;
                case "Leader"://Leader
                    $form->addButton(TextFormat::DARK_AQUA."ClanWar", 1, "https://media.discordapp.net/attachments/412217468287713282/880547998449418351/swords-312440_960_720.png?width=402&height=402", "cw");
                    $form->addButton(TextFormat::YELLOW."Clan Info", 1, "https://media.discordapp.net/attachments/412217468287713282/880559672472535120/info.png?width=402&height=402", "clan_info");
                    $form->addButton(TextFormat::GREEN."ClanWar History", 1, "https://media.discordapp.net/attachments/412217468287713282/885513348018470962/1800196.png?width=410&height=410", "cw_history");
                    $form->addButton(TextFormat::GREEN."Co".TextFormat::AQUA."l".TextFormat::GOLD."or", 1, "https://media.discordapp.net/attachments/412217468287713282/880895769622753380/551221.png?width=402&height=402", "color");
                    $form->addButton(TextFormat::DARK_PURPLE."Display information", 1, "https://media.discordapp.net/attachments/412217468287713282/880896093041348628/3076404.png?width=402&height=402", "display_info");
                    $form->addButton(TextFormat::GOLD."TOP 10", 1, "https://media.discordapp.net/attachments/412217468287713282/880530145331519538/954-9547395_file-emoji-u1f3c5-svg-wikimedia-commons-3rd-place.png?width=275&height=402", "top_clans");
                    $form->addButton(TextFormat::GOLD."Clan open state", 1, $openingState, "state");
                    $form->addButton(TextFormat::AQUA."Invite player", 1, "https://media.discordapp.net/attachments/412217468287713282/880558165022879775/invite.png?width=402&height=402", "invite");
                    $form->addButton(TextFormat::RED."Kick Player", 1, "https://media.discordapp.net/attachments/412217468287713282/880544897587896381/user-interface-remove-friend-block-kick-glyph-512.png?width=402&height=402", "kick");
                    $form->addButton(TextFormat::GREEN."Role update", 1, "https://media.discordapp.net/attachments/412217468287713282/880869858668052510/clan_role.png?width=224&height=224", "role_update");
                    $form->addButton(TextFormat::RED."Delete your clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880547596467331152/delete_clan.png?width=256&height=256", "delete");
                    break;
            }
        }else{//NO CLAN
            $form->addButton(TextFormat::DARK_AQUA."Create Clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880508828695793716/Iron_Sword.png?width=402&height=402", "create_clan");
            $form->addButton(TextFormat::AQUA."Requests", 1, "https://media.discordapp.net/attachments/412217468287713282/880559138579558460/request.png?width=402&height=402", "requests");
            $form->addButton(TextFormat::RED."Search Clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880528523402547260/Search_Clan.png?width=348&height=384", "search_clan");
            $form->addButton(TextFormat::GOLD."TOP 10", 1, "https://media.discordapp.net/attachments/412217468287713282/880530145331519538/954-9547395_file-emoji-u1f3c5-svg-wikimedia-commons-3rd-place.png?width=275&height=402", "top_clans");
        }
        $form->setTitle(TextFormat::GOLD.TextFormat::BOLD."Clans");
        $form->sendToPlayer($player);
    }
}