<?php

namespace baubolp\core\form\clan;

use BauboLP\Cloud\CloudBridge;
use baubolp\core\provider\AsyncExecutor;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ClanMainForm {

    /**
     * @param Player $player
     * @param array $extraData
     */
    public static function open(Player $player, array $extraData){
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
                    $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Wir arbeiten gerade dran!");
                    //TODO:
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
                    CloudBridge::getCloudProvider()->dispatchProxyCommand($playerName, "clan info");
                    //TODO: Maybe Info Form?
                    break;
                case "leave":
                    CloudBridge::getCloudProvider()->dispatchProxyCommand($playerName, "clan leave");
                    break;
                case "invite":
                    InvitePlayerForm::open($player);
                    break;
                case "kick":
                    KickClanMemberForm::open($player, $extraData);
                    break;
                case "delete":
                    CloudBridge::getCloudProvider()->dispatchProxyCommand($playerName, "clan delete");
                    break;
                case "cw":
                    JoinLeaveQueueOptionForm::open($player, $extraData);
                    break;
            }
        });
        if(isset($extraData["clanName"])){//IS IN CLAN
            switch($extraData["role"]){
                case "Member": //MEMBER
                    $form->addButton(TextFormat::YELLOW."Clan Info", 1, "https://media.discordapp.net/attachments/412217468287713282/880559672472535120/info.png?width=402&height=402", "clan_info");
                    $form->addButton(TextFormat::GOLD."TOP 10", 1, "https://media.discordapp.net/attachments/412217468287713282/880530145331519538/954-9547395_file-emoji-u1f3c5-svg-wikimedia-commons-3rd-place.png?width=275&height=402", "top_clans");
                    $form->addButton(TextFormat::RED."Leave Clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880541899390324786/leave_clan.png?width=205&height=205", "leave");
                    break;
                case "Moderator"://MODERATOR
                    $form->addButton(TextFormat::DARK_AQUA."ClanWar", 1, "https://media.discordapp.net/attachments/412217468287713282/880547998449418351/swords-312440_960_720.png?width=402&height=402", "cw");
                    $form->addButton(TextFormat::YELLOW."Clan Info", 1, "https://media.discordapp.net/attachments/412217468287713282/880559672472535120/info.png?width=402&height=402", "clan_info");
                    $form->addButton(TextFormat::GOLD."TOP 10", 1, "https://media.discordapp.net/attachments/412217468287713282/880530145331519538/954-9547395_file-emoji-u1f3c5-svg-wikimedia-commons-3rd-place.png?width=275&height=402", "top_clans");
                    $form->addButton(TextFormat::AQUA."Invite player", 1, "https://media.discordapp.net/attachments/412217468287713282/880558165022879775/invite.png?width=402&height=402", "invite");
                    $form->addButton(TextFormat::RED."Kick Player", 1, "https://media.discordapp.net/attachments/412217468287713282/880544897587896381/user-interface-remove-friend-block-kick-glyph-512.png?width=402&height=402", "kick");
                    $form->addButton(TextFormat::RED."Leave Clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880541899390324786/leave_clan.png?width=205&height=205", "leave");
                    break;
                case "Leader"://Leader
                    $form->addButton(TextFormat::DARK_AQUA."ClanWar", 1, "https://media.discordapp.net/attachments/412217468287713282/880547998449418351/swords-312440_960_720.png?width=402&height=402", "cw");
                    $form->addButton(TextFormat::YELLOW."Clan Info", 1, "https://media.discordapp.net/attachments/412217468287713282/880559672472535120/info.png?width=402&height=402", "clan_info");
                    $form->addButton(TextFormat::GOLD."TOP 10", 1, "https://media.discordapp.net/attachments/412217468287713282/880530145331519538/954-9547395_file-emoji-u1f3c5-svg-wikimedia-commons-3rd-place.png?width=275&height=402", "top_clans");
                    $form->addButton(TextFormat::AQUA."Invite player", 1, "https://media.discordapp.net/attachments/412217468287713282/880558165022879775/invite.png?width=402&height=402", "invite");
                    $form->addButton(TextFormat::RED."Kick Player", 1, "https://media.discordapp.net/attachments/412217468287713282/880544897587896381/user-interface-remove-friend-block-kick-glyph-512.png?width=402&height=402", "kick");
                #    $form->addButton(TextFormat::GREEN."Role update", 1, "https://media.discordapp.net/attachments/412217468287713282/880544897587896381/user-interface-remove-friend-block-kick-glyph-512.png?width=402&height=402", "role");
                    //todo: baubo -> i do it tomorrow! GOOD NIGHT! ;)
                    $form->addButton(TextFormat::RED."Delete your clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880547596467331152/delete_clan.png?width=256&height=256", "delete");
                    break;
            }
        }else{//NO CLAN
            $form->addButton(TextFormat::DARK_AQUA."Create Clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880508828695793716/Iron_Sword.png?width=402&height=402", "create_clan");
            $form->addButton(TextFormat::AQUA."Requests", 1, "https://media.discordapp.net/attachments/412217468287713282/880559138579558460/request.png?width=402&height=402", "requests");
            $form->addButton(TextFormat::RED."Search Clan", 1, "https://media.discordapp.net/attachments/412217468287713282/880528523402547260/Search_Clan.png?width=348&height=384", "search_clan");
            $form->addButton(TextFormat::GOLD."TOP 10", 1, "https://media.discordapp.net/attachments/412217468287713282/880530145331519538/954-9547395_file-emoji-u1f3c5-svg-wikimedia-commons-3rd-place.png?width=275&height=402", "top_clans");
        }
        $form->sendToPlayer($player);
    }
}