<?php

namespace ryzerbe\core\player;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerDisconnectPacket;
use BauboLP\Cloud\Packets\PlayerMoveServerPacket;
use BauboLP\Cloud\Provider\CloudProvider;
use DateTime;
use Exception;
use mysqli;
use pocketmine\entity\Skin;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\clan\Clan;
use ryzerbe\core\event\player\RyZerPlayerAuthEvent;
use ryzerbe\core\form\types\LanguageForm;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\player\chatmod\PlayerChatMod;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\player\data\NickInfo;
use ryzerbe\core\player\networklevel\NetworkLevel;
use ryzerbe\core\player\setting\PlayerSettings;
use ryzerbe\core\provider\CoinProvider;
use ryzerbe\core\provider\NickProvider;
use ryzerbe\core\provider\PartyProvider;
use ryzerbe\core\provider\PlayerSkinProvider;
use ryzerbe\core\provider\PunishmentProvider;
use ryzerbe\core\rank\Rank;
use ryzerbe\core\rank\RankManager;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\Coinboost;
use ryzerbe\core\util\punishment\PunishmentReason;
use ryzerbe\core\util\Settings;
use ryzerbe\core\util\skin\SkinDatabase;
use ryzerbe\core\util\time\TimeAPI;
use function array_key_exists;
use function array_search;
use function explode;
use function implode;
use function in_array;
use function str_replace;
use function stripos;
use function strlen;

class RyZerPlayer {
    private LoginPlayerData $loginPlayerData;
    private Player $player;

    private ?NetworkLevel $networkLevel;
    private ?NickInfo $nickInfo = null;

    private string $languageName = "English";
    private string $muteReason = "???";
    private string $id = "???";

    private int $coins = 0;
    public int $gameTimeTicks = 0;

    private Rank $rank;

    private ?Clan $clan = null;
    private PlayerSettings $playerSettings;
    private ?DateTime $mute = null;
    private ?Coinboost $coinboost = null;
    private PlayerChatMod $chatMod;

    /** @var array  */
    private array $myPermissions = [];

    /** @var Skin  */
    private Skin $skin;

    public function __construct(Player $player, LoginPlayerData $playerData){
        $this->player = $player;
        $this->loginPlayerData = $playerData;
        $this->rank = RankManager::getInstance()->getBackupRank();
        $this->skin = $player->getSkin();
        $this->playerSettings = new PlayerSettings();
        $this->chatMod = new PlayerChatMod();
    }

    /**
     * @return PMMPPlayer
     */
    public function getPlayer(): Player{
        return $this->player;
    }

    public function getPlayerSettings(): PlayerSettings{
        return $this->playerSettings;
    }

    public function getRank(): Rank{
        return $this->rank;
    }

    public function setRank(Rank $rank, bool $pushPermissions = true, bool $changePrefix = true, bool $mysql = false, bool|DateTime $permanent = true): void{
        $this->rank = $rank;

        if($changePrefix) {
            $this->getPlayer()->setNameTag(str_replace("{player_name}", $this->getPlayer()->getName(), $rank->getNameTag()));
            $this->getPlayer()->setDisplayName(str_replace("{player_name}", $this->getPlayer()->getName(), $rank->getNameTag()));
        }
        if($pushPermissions) $this->getPlayer()->addAttachment(RyZerBE::getPlugin())->setPermissions($rank->getPermissionFormat());
        if($mysql) RankManager::getInstance()->setRank($this->getPlayer()->getName(), $rank, $permanent);
    }

    public function addPlayerPermission(string $permission, bool $pushInstant = true, bool $mysql = false){
        $this->myPermissions[] = $permission;
        if($pushInstant) $this->getPlayer()->addAttachment(RyZerBE::getPlugin())->setPermission($permission, true);

        if($mysql) {
            $permissions = implode(";", $this->myPermissions);
            $playerName = $this->getPlayer()->getName();
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use($permissions, $playerName): void{
                $mysqli->query("UPDATE playerranks SET permissions='$permissions' WHERE player='$playerName'");
            });
        }
    }

    public function removePlayerPermission(string $permission, bool $pushInstant = true, bool $mysql = false){
        unset($this->myPermissions[array_search($permission, $this->myPermissions)]);
        if($pushInstant) $this->getPlayer()->addAttachment(RyZerBE::getPlugin())->setPermissions($this->myPermissions);

        if($mysql) {
            $permissions = implode(";", $this->myPermissions);
            $playerName = $this->getPlayer()->getName();
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use($permissions, $playerName): void{
                $mysqli->query("UPDATE playerranks SET permissions='$permissions' WHERE player='$playerName'");
            });
        }
    }

    public function addPlayerPermissions(array $permissions, bool $pushInstant = true, bool $mysql = false){
        foreach($permissions as $permission){
            $this->addPlayerPermission($permission, $pushInstant);
        }

        if($mysql) {
            $permissions = implode(";", $this->myPermissions);
            $playerName = $this->getPlayer()->getName();
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use($permissions, $playerName): void{
                $mysqli->query("UPDATE playerranks SET permissions='$permissions' WHERE player='$playerName'");
            });
        }
    }

    public function addCoins(int $coins, bool $isBoosted = false, bool $mysql = false){
        $this->coins += $coins;
        if($mysql) CoinProvider::addCoins($this->getPlayer()->getName(), $coins, $isBoosted);
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
        $player = $this->getPlayer();
        $playerName = $player->getName();
        $mysqlData = Settings::$mysqlLoginData;
        $loginPlayerData = $this->getLoginPlayerData();
        $address = $loginPlayerData->getAddress();
        $mc_id = $loginPlayerData->getMinecraftId();
        $device_id = $loginPlayerData->getDeviceId();
        $device_os = $loginPlayerData->getDeviceOs();
        $device_input = $loginPlayerData->getCurrentInputMode();
        $server = CloudProvider::getServer();
        $nowFormat = (new DateTime())->format("Y-m-d H:i");
        $skinData = $player->getSkin()->getSkinData();
        $geometryName = $player->getSkin()->getGeometryName();

        $correct_size = Skin::ACCEPTED_SKIN_SIZES[2];
        if(strlen($skinData) > $correct_size) {
            SkinDatabase::getInstance()->loadSkin("steve", function(bool $success) use ($player): void{
                if(!$player->isConnected() || !$player instanceof PMMPPlayer) return;
                if(!$success) $player->kickFromProxy("&cSkin aren't allowed! Please switch your skin!");
                else $player->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Skin aren't allowed!");
            }, null, $player);
        }

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $mysqlData, $address, $device_os, $device_id, $device_input, $mc_id, $server, $nowFormat, $skinData, $geometryName): array{
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

                        $type =($data["type"] == PunishmentReason::BAN) ? "ban" : "mute";
                        $playerData[$type."_until"] = $data["until"];
                        $playerData[$type."_staff"] = $data["created_by"];
                        $playerData[$type."_reason"] = $data["reason"];
                        $playerData[$type."_id"] = $data["id"];
                    }
                }
            }

            $mysqli->query("INSERT INTO `playerdata`(`player`, `ip_address`, `device_id`, `device_os`, `device_input`, `server`, `last_join`, `minecraft_id`) VALUES ('$playerName', '$address', '$device_id', '$device_os', '$device_input', '$server', '$nowFormat', '$mc_id') ON DUPLICATE KEY UPDATE device_id='$device_id',device_os='$device_os',device_input='$device_input',server='$server',last_join='$nowFormat',minecraft_id='$mc_id'");
            PlayerSkinProvider::storeSkin($playerName, $skinData, $geometryName, $mysqli);
            $res = $mysqli->query("SELECT * FROM coins WHERE player='$playerName'");
            if($res->num_rows > 0){
                $playerData["coins"] = $res->fetch_assoc()["coins"] ?? 0;
            }else{
                $mysqli->query("INSERT INTO `coins`(`player`) VALUES ('$playerName')");
                $playerData["coins"] = 0;
            }

            $res = $mysqli->query("SELECT * FROM playerranks WHERE player='$playerName'");
            if($res->num_rows > 0){
                $data = $res->fetch_assoc();
                $playerData["rank"] = $data["rankname"] ?? "Player";
                $playerData["permissions"] = $data["permissions"] ?? "";
                $playerData["rank_duration"] = $data["duration"] ?? 0;
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
            $res = $mysqli->query("SELECT * FROM `coinboosts` WHERE player='$playerName'");
            if($res->num_rows > 0) {
                if($data = $res->fetch_assoc()) {
                    $playerData["coinboost"] = [
                        "time" => $data["time"],
                        "percent" => $data["percent"],
                        "forAll" => $data["for_all"]
                    ];
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

            $activeNicks = NickProvider::getActiveNicks($mysqli, true);
            if(isset($activeNicks[$playerName])) $playerData["nick"] = $activeNicks[$playerName];

            $lobby->close();
            $clanDB->close();
            return $playerData;
        }, function(Server $server, array $playerData) use ($playerName): void{
            $player = $server->getPlayer($playerName);
            if(!$player instanceof PMMPPlayer) return;

            $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);

            if($ryzerPlayer === null) return;
            if($playerData["language"] === null) {
                LanguageForm::onOpen($player);
            }else {
                $ryzerPlayer->setLanguage($playerData["language"] ?? "English");
            }

            if(isset($playerData["ban_until"])) {
                $ryzerPlayer->kick(LanguageProvider::getMessage("ban-screen", $ryzerPlayer->getLanguageName(), ["#staff" => $playerData["ban_staff"], "#until" => PunishmentProvider::getUntilFormat($playerData["ban_until"]), "#reason" => $playerData["ban_reason"], "#id" => $playerData["ban_id"]]));
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
                $ryzerPlayer->setMute(new DateTime(($playerData["mute_until"] === "PERMANENT" || $playerData["mute_until"] == "0") ? "2040-10-11 23:59" : $playerData["mute_until"]));
                $ryzerPlayer->setMuteId($playerData["mute_id"]);
                $ryzerPlayer->setMuteReason($playerData["mute_reason"]);
            }

            if(isset($playerData["nick"])) {
                $ryzerPlayer->setNick(new NickInfo($playerData["nick"]["nickName"], $playerData["nick"]["skin"], $playerData["nick"]["level"]));
                SkinDatabase::getInstance()->loadSkin($playerData["nick"]["skin"], function(bool $success): void{}, "nick", $ryzerPlayer->getPlayer());
                $ryzerPlayer->getPlayer()->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("nick-active", $ryzerPlayer->getPlayer()));
            }

            $ryzerPlayer->setCoins($playerData["coins"] ?? 0);
            $ryzerPlayer->gameTimeTicks = $playerData["ticks"] ?? 0;

            $rank = RankManager::getInstance()->getRank($playerData["rank"] ?? "Player");
            if($rank === null) $rank = RankManager::getInstance()->getBackupRank();
            $ryzerPlayer->setRank($rank, true, false);
            $ryzerPlayer->addPlayerPermissions(explode(";", $playerData["permissions"] ?? ""));
            if(isset($playerData["rank_duration"])) {
                if($playerData["rank_duration"] != 0) {
                    $now = new DateTime();
                    $duration = new DateTime($playerData["rank_duration"]);
                    if($now > $duration){
                        $ryzerPlayer->getPlayer()->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("rank-expired", $player->getName(), ["#rank" => $rank->getColor().$rank->getRankName()]));
                        $ryzerPlayer->setRank(RankManager::getInstance()->getBackupRank(), true, false, true);
                    }else {
                        $ryzerPlayer->getRank()->setDuration($playerData["rank_duration"]);
                    }
                }
            }

            if(isset($playerData["coinboost"])) {
                $ryzerPlayer->setCoinboost(new Coinboost($player, (int)$playerData["coinboost"]["percent"] ?? 15, new DateTime($playerData["coinboost"]["time"]) ?? new DateTime(), (bool)$playerData["coinboost"]["forAll"] ?? false));
            }

            $ryzerPlayer->setNetworkLevel(new NetworkLevel($ryzerPlayer, $playerData["network_level"], $playerData["network_level_progress"], $playerData["level_progress_today"], strtotime($playerData["last_level_progress"])));

            if($playerData['clan'] !== "null" && !empty($playerData['clan'])) {
                $ryzerPlayer->setClan(new Clan($playerData["clan"], $playerData["clanColor"].$playerData["clanTag"], (int)$playerData["clanElo"], $playerData["owner"]));
            }


            if(isset($playerData["more_particle"])) {
                $ryzerPlayer->getPlayerSettings()->setMoreParticle($playerData["more_particle"]);
                $ryzerPlayer->getPlayerSettings()->setToggleRank($playerData["toggle_rank"]);
                $ryzerPlayer->getPlayerSettings()->setFriendRequestsEnabled($playerData["friend_requests"]);
                $ryzerPlayer->getPlayerSettings()->setPartyInvitesEnabled($playerData["party_requests"]);
                $ryzerPlayer->getPlayerSettings()->setMsgEnabled($playerData["msg_toggle"]);
            }

            if(isset($playerData["party_members"]) && stripos(CloudProvider::getServer(), "CWBW") === false && stripos(CloudProvider::getServer(), "Lobby") === false) {
                $pk = new PlayerMoveServerPacket();
                unset($playerData["party_members"][array_search($playerName, $playerData["party_members"])]);
                $pk->addData("playerNames", implode(":", $playerData["party_members"]));
                $pk->addData("serverName", CloudProvider::getServer());
                CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            }

            $ryzerPlayer->updateStatus($playerData["status"] ?? null);
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
            if($gameTimeTicks > 60) $mysqli->query("UPDATE gametime SET ticks='$gameTimeTicks' WHERE player='$playerName'");
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
        $pk->addData("message", str_replace("§", "&", $reason));
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

        if($this->getPlayerSettings()->isRankToggled() || $this->getNick() !== null){
            $nametag = str_replace("{player_name}", $this->getName(true), RankManager::getInstance()->getBackupRank()->getNameTag()); //PLAYER = DEFAULT
        }else{
            $nametag = str_replace("{player_name}", $this->getName(true), $this->getRank()->getNameTag());
        }
        $nametag = str_replace("&", TextFormat::ESCAPE, $nametag);

        $level = ($this->getNickInfo() !== null) ? $this->getNickInfo()->getLevel() : $this->getNetworkLevel()->getLevel();
        $player->setNameTag($nametag.TextFormat::BLACK." [".$this->getNetworkLevel()->getLevelColor($level).$level.TextFormat::BLACK."]"."\n".TextFormat::YELLOW.(($status !== null && $this->getNickInfo() === null) ? "✎ ".$status : TextFormat::YELLOW.$this->getLoginPlayerData()->getDeviceOsName()));
        $player->setDisplayName($nametag);
    }

    /**
     * @return string|null
     */
    public function getNick(): ?string{
        return ($this->nickInfo === null) ? null : $this->nickInfo->getNickName();
    }

    /**
     * @param NickInfo|null $nick
     */
    public function setNick(?NickInfo $nick): void{
        $this->nickInfo = $nick;
    }

    /**
     * @return NickInfo|null
     */
    public function getNickInfo(): ?NickInfo{
        return $this->nickInfo;
    }

    /**
     * @return Skin
     */
    public function getJoinSkin(): Skin{
        return $this->skin;
    }

    /**
     * @param bool $nick
     * @return string
     */
    public function getName(bool $nick): string{
        return ($nick === true && $this->nickInfo !== null) ? $this->nickInfo->getNickName() : $this->getPlayer()->getName();
    }

    public function sendTranslate(string $key, array $replaces = [], string $prefix = RyZerBE::PREFIX): void{
        $this->getPlayer()->sendMessage($prefix.LanguageProvider::getMessageContainer($key, $this->getPlayer(), $replaces));
    }

    /**
     * @return Coinboost|null
     */
    public function getCoinboost(): ?Coinboost{
        return $this->coinboost;
    }

    /**
     * @param Coinboost|null $coinboost
     */
    public function setCoinboost(?Coinboost $coinboost): void{
        $this->coinboost = $coinboost;
    }

    /**
     * @param int $percent
     * @param DateTime $endTime
     * @param bool $isForAll
     * @param bool $mysql
     */
    public function giveCoinboost(int $percent, DateTime $endTime, bool $isForAll = false, bool $mysql = false){
        $this->setCoinboost(new Coinboost($this->getPlayer(), $percent, $endTime, $isForAll));

        if($mysql) {
            $name = $this->getPlayer()->getName();
            $endTime = $endTime->format("Y-m-d H:i");
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name, $isForAll, $percent, $endTime){
                $mysqli->query("INSERT INTO `coinboosts`(`player`, `time`, `percent`, `for_all`) VALUES ('$name', '$endTime', '$percent', '$isForAll')");
            });
        }
    }

    /**
     * @return PlayerChatMod
     */
    public function getChatModData(): PlayerChatMod{
        return $this->chatMod;
    }

	public function nick(): void{
    	if($this->isNicked()) return;
		NickProvider::nick($this->getPlayer());
    }

	public function isNicked(){
		return $this->getNick() !== null;
    }

	public function toggleNick(): void{
		if($this->isNicked()) $this->unnick();
		else $this->nick();
    }

	public function unnick(): void{
		if(!$this->isNicked()) return;

		NickProvider::unnick($this->getPlayer());
    }
}