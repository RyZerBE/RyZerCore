<?php

namespace ryzerbe\core\player;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerDisconnectPacket;
use BauboLP\Cloud\Packets\PlayerMoveServerPacket;
use BauboLP\Cloud\Provider\CloudProvider;
use DateTime;
use Exception;
use mysqli;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\player\networklevel\NetworkLevel;
use ryzerbe\core\player\setting\PlayerSettings;
use ryzerbe\core\provider\CoinProvider;
use ryzerbe\core\provider\PartyProvider;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\clan\Clan;
use ryzerbe\core\util\punishment\PunishmentReason;
use ryzerbe\core\util\Settings;
use ryzerbe\core\util\time\TimeAPI;
use function array_key_exists;
use function explode;
use function implode;
use function in_array;
use function str_replace;
use function stripos;
use function var_dump;

class RyZerPlayer {
    private LoginPlayerData $loginPlayerData;
    private Player $player;

    private ?NetworkLevel $networkLevel;

    private string $languageName = "English";
    private string $muteReason = "???";
    private string $id = "???";
    /** @var string|null  */
    private ?string $nick = null;

    private int $coins = 0;
    public int $gameTimeTicks = 0;

    private Rank $rank;

    private ?Clan $clan;
    private PlayerSettings $playerSettings;
    private ?DateTime $mute = null;

    public function __construct(Player $player, LoginPlayerData $playerData){
        $this->player = $player;
        $this->loginPlayerData = $playerData;
        $this->rank = RankManager::getInstance()->getBackupRank();
        $this->playerSettings = new PlayerSettings();
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getPlayerSettings(): PlayerSettings{
        return $this->playerSettings;
    }

    public function getRank(): Rank{
        return $this->rank;
    }

    public function setRank(Rank $rank, bool $pushPermissions = true, bool $changePrefix = true, bool $mysql = false): void{
        $this->rank = $rank;

        if($changePrefix) {
            $this->getPlayer()->setNameTag(str_replace("{player_name}", $this->getPlayer()->getName(), $rank->getNameTag()));
            $this->getPlayer()->setDisplayName(str_replace("{player_name}", $this->getPlayer()->getName(), $rank->getNameTag()));
        }
        if($pushPermissions) $this->getPlayer()->addAttachment(RyZerBE::getPlugin())->setPermissions($rank->getPermissionFormat());
        if($mysql) RankManager::getInstance()->setRank($this->getPlayer()->getName(), $rank);
    }

    public function addCoins(int $coins, bool $mysql = false){
        $this->coins += $coins;
        if($mysql) CoinProvider::addCoins($this->getPlayer()->getName(), $coins);
    }

    public function removeCoins(int $coins, bool $mysql = false){
        $this->coins -= $coins;
        if($mysql) CoinProvider::removeCoins($this->getPlayer()->getName(), $coins);
    }

    public function setCoins(int $coins, bool $mysql = false){
        $this->coins = $coins;
        if($mysql) CoinProvider::setCoins($this->getPlayer()->getName(), $coins);
    }

    public function getCoins(): int{
        return $this->coins;
    }

    public function getLoginPlayerData(): LoginPlayerData{
        return $this->loginPlayerData;
    }

    public function getNetworkLevel(): ?NetworkLevel{
        return $this->networkLevel;
    }

    public function setNetworkLevel(?NetworkLevel $networkLevel): void{
        $this->networkLevel = $networkLevel;
    }

    public function loadData(): void{
        $playerName = $this->getPlayer()->getName();
        $mysqlData = Settings::$mysqlLoginData;
        $loginPlayerData = $this->getLoginPlayerData();
        $address = $loginPlayerData->getAddress();
        $mc_id = $loginPlayerData->getMinecraftId();
        $device_id = $loginPlayerData->getDeviceId();
        $device_os = $loginPlayerData->getDeviceOs();
        $device_input = $loginPlayerData->getCurrentInputMode();
        $server = CloudProvider::getServer();
        $nowFormat = (new DateTime())->format("Y-m-d H:i");

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $mysqlData, $address, $device_os, $device_id, $device_input, $mc_id, $server, $nowFormat): array{
            $playerData = [];
            $res = $mysqli->query("SELECT * FROM playerlanguage WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["language"] = $res->fetch_assoc()["language"] ?? "English";
            }else{
                $mysqli->query("INSERT INTO `playerlanguage`(`player`) VALUES ('$playerName')");
                $playerData["language"] = null;
            }

            $res = $mysqli->query("SELECT * FROM punishments WHERE player='$playerName'");
            if($res->num_rows > 0) {
                while($data = $res->fetch_assoc()) {
                    if(PunishmentProvider::activatePunishment($data["until"])) {
                        $untilString = PunishmentProvider::getUntilFormat($data["until"]);

                        $type =($data["type"] == PunishmentReason::BAN) ? "ban" : "mute";
                        $playerData[$type."_until"] = $untilString;
                        $playerData[$type."_staff"] = $data["created_by"];
                        $playerData[$type."_reason"] = $data["reason"];
                        $playerData[$type."_id"] = $data["id"];
                    }
                }
            }

            $mysqli->query("INSERT INTO `playerdata`(`player`, `ip_address`, `device_id`, `device_os`, `device_input`, `server`, `last_join`, `minecraft_id`) VALUES ('$playerName', '$address', '$device_id', '$device_os', '$device_input', '$server', '$nowFormat', '$mc_id') ON DUPLICATE KEY UPDATE device_id='$device_id',device_os='$device_os',device_input='$device_input',server='$server',last_join='$nowFormat',minecraft_id='$mc_id'");

            $res = $mysqli->query("SELECT * FROM coins WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["coins"] = $res->fetch_assoc()["coins"] ?? 0;
            }else{
                $mysqli->query("INSERT INTO `coins`(`player`) VALUES ('$playerName')");
                $playerData["coins"] = 0;
            }

            $res = $mysqli->query("SELECT * FROM playerranks WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["rank"] = $res->fetch_assoc()["rankname"] ?? "Player";
                $playerData["permissions"] = $res->fetch_assoc()["permissions"] ?? "";
            }

            $res = $mysqli->query("SELECT * FROM gametime WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["ticks"] = $res->fetch_assoc()["ticks"] ?? 0;
            }else{
                $mysqli->query("INSERT INTO `gametime`(`player`) VALUES ('$playerName')");
            }

            $result = $mysqli->query("SELECT * FROM networklevel WHERE playername='$playerName'");
            if ($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    $playerData["network_level_progress"] = $data["level_progress"];
                    $playerData["network_level"] = $data["level"];
                    $playerData["level_progress_today"] = $data["level_progress_today"];
                    $playerData["last_level_progress"] = $data["last_level_progress"];
                }
            } else {
                $mysqli->query("INSERT INTO `networklevel`(`playername`) VALUES ('$playerName')");
                $playerData["network_level_progress"] = 0;
                $playerData["network_level"] = 1;
                $playerData["level_progress_today"] = 0;
                $playerData["last_level_progress"] = 0;
            }

            $clanDB = new mysqli($mysqlData["host"], $mysqlData["username"], $mysqlData["password"], "BetterClans");
            $result = $clanDB->query("SELECT * FROM ClanUsers WHERE playername='$playerName'");
            if($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    $playerData['clan'] = $data['clan_name'];
                }
            }else {
                $playerData['clan'] = null;
            }

            $clanName = $playerData['clan'];
            if($clanName !== null && $clanName !== "") {
                $result = $clanDB->query("SELECT * FROM Clans WHERE clan_name='$clanName'");
                if($result->num_rows > 0) {
                    while($data = $result->fetch_assoc()) {
                        $playerData['clanColor'] = $data['color'];
                        $playerData['clanTag'] = $data['clan_tag'];
                        $playerData['owner'] = $data['clan_owner'];
                        $playerData["clanElo"] = $data["elo"];
                    }
                }else {
                    $playerData['clanTag'] = "";
                    $playerData['clanColor'] = "§e";
                    $playerData["clanElo"] = 1000;
                }
            }else {
                $playerData['clanTag'] = "";
                $playerData['clanColor'] = "§e";
                $playerData["clanElo"] = 1000;
            }

            $lobby = new mysqli($mysqlData['host'], $mysqlData['username'], $mysqlData['password'], 'Lobby');
            $result = $lobby->query("SELECT * FROM `Status` WHERE playername='$playerName'");
            if($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    $status = $data['status'];
                    if($status == "false") {
                        $playerData['status'] = null;
                    }else {
                        $playerData['status'] = $status;
                    }
                }
            }else {
                $playerData['status'] = null;
            }

            $partyRole = PartyProvider::getPlayerRole($mysqli, $playerName, false);
            if($partyRole === PartyProvider::PARTY_ROLE_LEADER) {
                $party = PartyProvider::getPartyByPlayer($mysqli, $playerName);
                if($party !== null) $playerData["party_members"] = PartyProvider::getPartyMembers($mysqli, $party);
            }

            $res = $mysqli->query("SELECT * FROM player_settings WHERE player='$playerName'");
            if($res->num_rows > 0) {
                while ($data = $res->fetch_assoc()) {
                    $playerData["msg_toggle"] = $data["msg_toggle"];
                    $playerData["party_requests"] = $data["party_requests"];
                    $playerData["friend_requests"] = $data["friend_requests"];
                    $playerData["toggle_rank"] = $data["toggle_rank"];
                    $playerData["more_particle"] = $data["more_particle"];
                }
            }
            $accounts = [];

            $res = $mysqli->query("SELECT * FROM second_accounts WHERE player='$playerName'");
            if($res->num_rows > 0) {
                if($data = $res->fetch_assoc()) {
                    $accounts = explode(":", $data["accounts"]);
                }
            }

            $res = $mysqli->query("SELECT * FROM `playerdata`");
            if($res->num_rows > 0) {
                while($data = $res->fetch_assoc()) {
                    if($data["minecraft_id"] === $mc_id || $data["ip_address"] === $address || $data["device_id"] === $device_id) {
                        if(!in_array($data["player"], $accounts)) $accounts[] = $data["player"];
                    }
                }
            }

            $mysqli->query("INSERT INTO `second_accounts`(`player`, `accounts`) VALUES ('$playerName', '".implode(":", $accounts)."') ON DUPLICATE KEY UPDATE accounts='".implode(":", $accounts)."'");

            $byPass = false;
            if(!array_key_exists("ban_until", $playerData)){
                foreach($accounts as $account){
                    $res = $mysqli->query("SELECT * FROM punishments WHERE player='$account'");
                    if($res->num_rows > 0){
                        while($data = $res->fetch_assoc()){
                            if(PunishmentProvider::activatePunishment($data["until"])){
                                if($data["type"] == PunishmentReason::BAN){
                                    $byPass = true;
                                    break;
                                }elseif($data["type"] == PunishmentReason::MUTE){
                                    if(!isset($playerData["mute_until"])) $playerData["mute"] = 11;
                                }
                            }
                        }
                        if($byPass) break;
                    }
                }


                if($byPass) $playerData["ban"] = $accounts;
            }

            $lobby->close();
            $clanDB->close();
            return $playerData;
        }, function(Server $server, array $playerData) use ($playerName): void{
            #var_dump($playerData);
            $player = $server->getPlayer($playerName);
            if($player === null) return;

            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);

            if($ryzerPlayer === null) return;
            if($playerData["language"] === null) {
                $player->getServer()->dispatchCommand($player, "lang");
            }else {
                $ryzerPlayer->setLanguage($playerData["language"] ?? "English");
            }

            if(isset($playerData["ban_until"])) {
                $ryzerPlayer->kick(LanguageProvider::getMessage("ban-screen", $ryzerPlayer->getLanguageName(), ["#staff" => $playerData["ban_staff"], "#until" => $playerData["ban_until"], "#reason" => $playerData["ban_reason"], "#id" => $playerData["ban_id"]]));
                return;
            }

            if(isset($playerData["ban"])) {
                foreach($playerData["ban"] as $account) {
                    PunishmentProvider::punishPlayer($account, "System", 8);
                }
                return;
            }

            if(isset($playerData["mute"])) {
                $ryzerPlayer->punish(PunishmentProvider::getPunishmentReasonById(9), "System");
                $ryzerPlayer->setMute(new DateTime("2040-10-11 23:59"));
                $ryzerPlayer->setMuteId("Rejoin to see it!");
                $ryzerPlayer->setMuteReason("Mute Bypass");
            }

            if(isset($playerData["mute_until"])) {
                $ryzerPlayer->setMute(new DateTime(($playerData["mute_until"] === "PERMANENT") ? "2040-10-11 23:59" : $playerData["mute_until"]));
                $ryzerPlayer->setMuteId($playerData["mute_id"]);
                $ryzerPlayer->setMuteReason($playerData["mute_reason"]);
            }

            $ryzerPlayer->setCoins($playerData["coins"] ?? 0);
            $ryzerPlayer->gameTimeTicks = $playerData["ticks"] ?? 0;


            $rank = RankManager::getInstance()->getRank($playerData["rank"] ?? "Player");
            if($rank === null) $rank = RankManager::getInstance()->getBackupRank();
            $ryzerPlayer->setRank($rank, true, false);

            $ryzerPlayer->setNetworkLevel(new NetworkLevel($ryzerPlayer, $playerData["network_level"], $playerData["network_level_progress"], $playerData["level_progress_today"], strtotime($playerData["last_level_progress"])));
            $ryzerPlayer->updateStatus($playerData["status"] ?? null);

            if($playerData['clan'] !== null && $playerData['clan'] !== "null") {
                $ryzerPlayer->setClan(new Clan($playerData["clan"], $playerData["clanColor"].$playerData["clanTag"], (int)$playerData["clanElo"], $playerData["owner"]));
            }


            if(isset($playerData["more_particle"])) {
                $ryzerPlayer->getPlayerSettings()->setMoreParticle($playerData["more_particle"]);
                $ryzerPlayer->getPlayerSettings()->setToggleRank($playerData["toggle_rank"]);
                $ryzerPlayer->getPlayerSettings()->setFriendRequestsEnabled($playerData["friend_requests"]);
                $ryzerPlayer->getPlayerSettings()->setPartyInvitesEnabled($playerData["party_requests"]);
                $ryzerPlayer->getPlayerSettings()->setMsgEnabled($playerData["msg_toggle"]);
            }

            if(isset($playerData["party_members"]) && stripos(CloudProvider::getServer(), "CWBW") === false) {
                $pk = new PlayerMoveServerPacket();
                $pk->addData("playerNames", implode(":", $playerData["party_members"]));
                $pk->addData("serverName", CloudProvider::getServer());
                CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            }

            $ev = new RyZerPlayerAuthEvent($ryzerPlayer);
            $ev->call();
        });
    }

    public function saveData(): void{
        $gameTimeTicks = $this->getGameTimeTicks();
        $playerName = $this->getPlayer()->getName();
        
        $settings = $this->getPlayerSettings();
        $more_particle = (int)$settings->isMoreParticleActivated();
        $party_invites = (int)$settings->isPartyInvitesEnabled();
        $friend_requests = (int)$settings->isFriendRequestsEnabled();
        $msg_toggle = (int)$settings->isMsgEnabled();
        $toggleRank = (int)$settings->isRankToggled();

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($gameTimeTicks, $playerName, $more_particle, $party_invites, $friend_requests, $msg_toggle, $toggleRank): void{
            $mysqli->query("UPDATE gametime SET ticks='$gameTimeTicks' WHERE player='$playerName'");
            $mysqli->query("INSERT INTO `player_settings`(`player`, `more_particle`, `party_requests`, `friend_requests`, `msg_toggle`, `toggle_rank`) VALUES ('$playerName', '$more_particle', '$party_invites', '$friend_requests', '$msg_toggle', '$toggleRank') ON DUPLICATE KEY UPDATE more_particle='$more_particle',party_requests='$party_invites',friend_requests='$friend_requests',msg_toggle='$msg_toggle',toggle_rank='$toggleRank'");
        });
    }

    public function setLanguage(string $languageName, bool $mysql = false): void{
        $this->languageName = $languageName;
        if($mysql) {
            $playerName = $this->getPlayer()->getName();
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($languageName, $playerName): void{
                $mysqli->query("UPDATE playerlanguage SET language='$languageName' WHERE player='$playerName'");
            });
        }
    }

    public function getLanguageName(): string{
        return $this->languageName;
    }

    public function getGameTimeTicks(): int{
        return $this->gameTimeTicks;
    }

    public function getOnlineTime(): string{
        return TimeAPI::convert($this->gameTimeTicks)->asShortString();
    }

    public function getClan(): ?Clan{
        return $this->clan;
    }

    public function setClan(?Clan $clan): void{
        $this->clan = $clan;
    }

    public function kick(string $reason){
        $pk = new PlayerDisconnectPacket();
        $pk->addData("playerName", $this->getPlayer()->getName());
        $pk->addData("message", $reason);
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }

    public function connectServer(string $serverName){
        $pk = new PlayerMoveServerPacket();
        $pk->addData("playerNames", $this->getPlayer()->getName());
        $pk->addData("serverName", $serverName);
        CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
    }

    public function sendToLobby(): void{
        CloudBridge::getCloudProvider()->dispatchProxyCommand($this->getPlayer()->getName(), "hub");
    }

    public function punish(PunishmentReason $reason, string $staff){
        try {
            PunishmentProvider::punishPlayer($this->getPlayer()->getName(), $staff, $reason);
        }catch(Exception $e) {}
    }

    public function unpunish(string $reason, string $staff, int $type = PunishmentReason::MUTE){
        PunishmentProvider::unpunishPlayer($this->getPlayer()->getName(), $staff, $reason, $type);
    }

    public function getMuteTime(): ?DateTime{
        return $this->mute;
    }

    public function getMuteId(): string{
        return $this->id;
    }

    public function getMute(): ?DateTime{
        return $this->mute;
    }

    public function getMuteReason(): string{
        return $this->muteReason;
    }

    public function setMuteReason(string $muteReason): void{
        $this->muteReason = $muteReason;
    }

    public function setMute(?DateTime $mute): void{
        $this->mute = $mute;
    }

    public function setMuteId(string $id): void{
        $this->id = $id;
    }

    public function updateStatus(?string $status): void{
        $player = $this->getPlayer();

        if($this->getPlayerSettings()->isRankToggled()){
            $nametag = str_replace("{player_name}", $player->getName(), RankManager::getInstance()->getBackupRank()->getNameTag()); //PLAYER = DEFAULT
        }else{
            $nametag = str_replace("{player_name}", $player->getName(), $this->getRank()->getNameTag());
        }
        $nametag = str_replace("&", TextFormat::ESCAPE, $nametag);

        $player->setNameTag($nametag.TextFormat::BLACK." [".$this->getNetworkLevel()->getLevelColor().$this->getNetworkLevel()->getLevel().TextFormat::BLACK."]"."\n".TextFormat::YELLOW.(($status !== null ? "✎ ".$status : TextFormat::YELLOW.$this->getLoginPlayerData()->getDeviceOsName())));
        $player->setDisplayName($nametag);
    }

    /**
     * @return string|null
     */
    public function getNick(): ?string{
        return $this->nick;
    }

    /**
     * @param bool $nick
     * @return string
     */
    public function getName(bool $nick): string{
        return ($nick === true && $this->nick !== null) ? $this->nick : $this->getPlayer()->getName();
    }
}