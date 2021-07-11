<?php

namespace baubolp\core\task;


use BauboLP\BW\BW;
use BauboLP\Cloud\Bungee\BungeeAPI;
use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\listener\own\RyZerPlayerAuthEvent;
use baubolp\core\player\RyzerPlayer;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\DiscordProvider;
use baubolp\core\provider\JoinMEProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\ModerationProvider;
use baubolp\core\provider\NickProvider;
use baubolp\core\provider\RankProvider;
use baubolp\core\provider\StaffProvider;
use baubolp\core\provider\VanishProvider;
use baubolp\core\provider\VIPJoinProvider;
use baubolp\core\Ryzer;
use baubolp\core\util\Webhooks;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class LoadAsyncDataTask extends AsyncTask
{
    /** @var array */
    private $loginData;

    private $mysqlData;
    /** @var array */
    private $os;
    /** @var string */
    private $date;
    /** @var string  */
    private $server;

    public function __construct(array $loginData, array $mysqlData)
    {
        $this->mysqlData = $mysqlData;
        $this->loginData = $loginData;
        $this->os = RyzerPlayer::$os;
        $now = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $this->date = $now->format("Y-m-d H:i:s");
        $this->server = CloudProvider::getServer();
    }

    /**
     * @inheritDoc
     */
    public function onRun()
    {
        $loginData = $this->loginData;
        $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
        $playerName = $loginData['playerName'];

        $playerData = [];
        $playerData['playerName'] = $playerName;

        ////LANGUAGE \\\\
        $result = $mysqli->query("SELECT selected_language FROM PlayerLanguage WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            while ($data = $result->fetch_assoc()) {
                $playerData['language'] = $data['selected_language'];
            }
        } else {
            $mysqli->query("INSERT INTO PlayerLanguage(`playername`, `selected_language`) VALUES ('$playerName', 'English')");
            $playerData['language'] = null;
        }


        ////PlayerData\\\\
        $clientId = $loginData['deviceId'];
        $clientModel = $loginData['deviceModel'];
        $address = $loginData['address'];

        $device = $this->os[$loginData['deviceOs']];
        $result = $mysqli->query("SELECT playername FROM PlayerData WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            $mysqli->query("UPDATE PlayerData SET ip='$address',clientid='$clientId',clientmodel='$clientModel',device='$device' WHERE playername='$playerName'");
        } else {
            $date = $this->date;
            $mysqli->query("INSERT INTO `PlayerData`(`playername`, `ip`, `clientid`, `clientmodel`, `mcid`, `accounts`, `device`, `firstjoin`) VALUES ('$playerName', '$address', '$clientId', '$clientModel', '', '', '$device', '$date')");
        }

        ////BAN \\\\
        $result = $mysqli->query("SELECT ban,banduration,banid,mute,muteduration,muteid FROM PlayerModeration WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            while ($data = $result->fetch_assoc()) {
                $playerData['reason'] = $data['ban'];
                $playerData['banduration'] = $data['banduration'];
                $playerData['banid'] = $data['banid'];
                $playerData['muteid'] = $data['muteid'];
                $playerData['muteduration'] = $data['muteduration'];
                $playerData['mute'] = $data['mute'];
            }
        } else {
            $mysqli->query("INSERT INTO `PlayerModeration`(`playername`, `ban`, `banduration`, `mute`, `muteduration`, `warns`, `log`, `banpoints`, `mutepoints`, `banid`, `muteid`, `unbanlog`) VALUES ('$playerName', '', '', '', '', '', '', '1', '1', '', '', '')");
            $playerData['reason'] = "";
            $playerData['banduration'] = "";
            $playerData['banid'] = "";
            $playerData['muteid'] = "";
            $playerData['muteduration'] = "";
            $playerData['mute'] = "";
        }

        //// R-PERMS \\\\
        $result = $mysqli->query("SELECT rankname,permissions FROM PlayerPerms WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['rank'] = $data['rankname'];
                $playerData['permissions'] = $data['permissions'];
            }
        } else {
            $mysqli->query("INSERT INTO `PlayerPerms`(`playername`, `rankname`, `permissions`) VALUES ('$playerName', 'Player', '')");
            $playerData['rank'] = "Player";
            $playerData['permissions'] = "";
        }

        //// COINS \\\\
        $result = $mysqli->query("SELECT coins FROM Coins WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['coins'] = $data['coins'];
            }
        } else {
            $mysqli->query("INSERT INTO `Coins`(`playername`, `coins`) VALUES ('$playerName', '1000')");
            $playerData['coins'] = '1000';

        }

        //// PARTICLE-MOD \\\\

        $result = $mysqli->query("SELECT * FROM ParticleMod WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while ($data = $result->fetch_assoc()) {
                $playerData['pm'] = (bool)$data['pm'];
            }
        }else {
            $mysqli->query("INSERT INTO `ParticleMod`(`playername`, `pm`) VALUES ('$playerName', false)");
            $playerData['pm'] = false;
        }

        $clanDB = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'Clans');
        $result = $clanDB->query("SELECT clan FROM ClanPlayers WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['clan'] = $data['clan'];
            }
        }else {
            $playerData['clan'] = null;
        }

        $clanName = $playerData['clan'];
        if($clanName != null && $clanName != "null") {
            $result = $clanDB->query("SELECT color,clantag FROM Clans WHERE clanname='$clanName'");
            if($result->num_rows > 0) {
                while($data = $result->fetch_assoc()) {
                    $playerData['clanColor'] = $data['color'];
                    $playerData['clantag'] = $data['clantag'];
                }
            }else {
                $playerData['clantag'] = "";
                $playerData['clanColor'] = "&e";
            }
        }else {
            $playerData['clantag'] = "";
            $playerData['clanColor'] = "&e";
        }

        ////ADD ACCOUNTS \\\\
        $result = $mysqli->query("SELECT playername,ip,clientid,accounts FROM `PlayerData`");
        $sameAccounts = "";
        if($result->num_rows > 0) {
            $playerData['nums'] = $result->num_rows;
            while ($data = $result->fetch_assoc()) {
                if($data['ip'] == $address || $data['clientid'] == $clientId) {
                    if(!in_array($playerName, explode(":", $data['accounts']))) {
                        if($playerName != $data['playername'])
                        $sameAccounts .= ":".$data['playername'];

                        if($data['accounts'] == "") {
                            $newAccounts = $playerName;
                        }else {
                            $newAccounts = $data['accounts'].":".$playerName;
                        }
                  //      var_dump("added Account $playerName to ".$data['playername']);
                        $accounts = explode(":", $data['accounts']);
                        if($accounts > 0) {
                            foreach ($accounts as $account) {
                                $mysqli->query("UPDATE `PlayerData` SET accounts='$newAccounts' WHERE playername='$account'");
                            }
                        }
                        $mysqli->query("UPDATE `PlayerData` SET accounts='$newAccounts' WHERE playername='$playerName'");
                    }
                }
            }
        }
        $result = $mysqli->query("SELECT accounts FROM `PlayerData` WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['accounts'] = explode(":", $data['accounts']);
                $playerData['accountsString'] = $data['accounts'];
            }
        }

        $accs = $playerData['accountsString'].$sameAccounts;
        $mysqli->query("UPDATE `PlayerData` SET accounts='$accs' WHERE playername='$playerName'");

        $result = $mysqli->query("SELECT accounts FROM `PlayerData` WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['accounts'] = explode(":", $data['accounts']);
            }
        }

        //// BANUMGEHUNG CHECK \\\\
        if($playerData['banduration'] == "") {
            if(count($playerData['accounts'] ) > 0) {
                foreach ($playerData['accounts'] as $account) {
                    $result = $mysqli->query("SELECT playername,banduration FROM `PlayerModeration` WHERE playername='$account'");
                    if($result->num_rows > 0) {
                        while($data = $result->fetch_assoc()) {
                            if($data['banduration'] != "" && $data['playername'] != $playerName) {
                                $playerData['banduration'] = 'Permanent';
                                $playerData['ban'] = "Banumgehung({$data['playername']})";
                                $playerData['banid'] = "/";
                                foreach ($playerData['accounts'] as $newAccount) {
                                    $mysqli->query("UPDATE `PlayerModeration` SET ban='Banumgehung($playerName)',banduration='Permanent',banid='/' WHERE playername='$newAccount'");
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        //// NICK \\\\
        $result = $mysqli->query("SELECT * FROM Nick WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['nick'] = $data['nick'];
                $playerData['nickSkin'] = $data['skin'];
            }
        }else {
            $playerData['nick'] = null;
        }

        //// GAME TIME \\\\
        $result = $mysqli->query("SELECT * FROM GameTime WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['gameTime'] = explode(":", $data['gametime']);
            }
        }else {
            $playerData['gameTime'] = [0, 0];
            $mysqli->query("INSERT INTO `GameTime`(`playername`, `gametime`) VALUES ('$playerName', '0:0')");
        }

        $result = $mysqli->query("SELECT * FROM ToggleRank WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            $playerData['toggleRank'] = true;
        }else {
            $playerData['toggleRank'] = false;
        }

        //// VANISH \\\\
        $result = $mysqli->query("SELECT * FROM vanish WHERE playername = '$playerName'");
        $playerData["isVanished"] = $result->num_rows > 0;

        $playerData['status'] = null;
        if(explode("-", $this->server)[0] == "Lobby") {
            $lobby = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'Lobby');
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
            $lobby->close();
        }

        $mysqli->close();
        $clanDB->close();
        $this->setResult($playerData);
    }


    public function onCompletion(Server $server)
    {
        $data = $this->getResult();
        if (($player = $server->getPlayerExact($data['playerName'])) != null) {
            /////LANGUAGE \\\\\
            ///
            if ($data['language'] == null) { //PLAYER IS NEW!
                Server::getInstance()->dispatchCommand($player, 'language');
                if (($obj = RyzerPlayerProvider::getRyzerPlayer($data['playerName'])) != null)
                    $obj->setLanguage("English");

                DiscordProvider::sendMessageToDiscord("RyZerBE", "**".$data['playerName']."** ist nun ein Spieler auf unserem Netzwerk **#".$data['nums']."**", Webhooks::NEW_PLAYERS_NETWORK);
            } else {
                if (($obj = RyzerPlayerProvider::getRyzerPlayer($data['playerName'])) != null)
                    $obj->setLanguage($data['language']);
            }



            /////BAN \\\\\
            if (($obj = RyzerPlayerProvider::getRyzerPlayer($data['playerName'])) != null) {
              if($data["isVanished"]) {
                  VanishProvider::vanishPlayer($obj, true);
                  $player->sendMessage(Ryzer::PREFIX.TextFormat::YELLOW."Du bist immer noch im Vanish!");
              }

                if ($data['banduration'] != "") {
                    if ($data['banduration'] != "Permanent") {
                        $now = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
                        $bantime = new \DateTime($data['banduration']);
                        if ($now < $bantime) {
                            BungeeAPI::kickPlayer($data['playerName'], ModerationProvider::getBanScreen($data['reason'], ($data['language'] == "Deutsch") ? ModerationProvider::formatGermanDate($data['banduration']) : $data['banduration'],
                                $data['banid'], $data['language'] == "English"));
                        } else {
                            ModerationProvider::unban($player->getName());
                        }
                    } else {
                        BungeeAPI::kickPlayer($data['playerName'], ModerationProvider::getBanScreen($data['reason'], "PERMANENT", $data['banduration']));
                    }
                }
            }

            ////MUTE \\\\
            $muteData = ['reason' => $data['mute'], 'duration' => $data['muteduration'], 'id' => $data['muteid']];
            if (($obj = RyzerPlayerProvider::getRyzerPlayer($data['playerName'])) != null) {
                if($muteData['reason'] != "") {
                    $obj->setMuteData($muteData);
                    $obj->setMuted(true);
                }
            }

            if(!RankProvider::existRank($data["rank"])) {
                $player->kick(Ryzer::PREFIX.LanguageProvider::getMessageContainer('something-went-wrong', $player->getName()), false);
                return;
            }

            //// PLAYER PERMS \\\\
            if (($obj = RyzerPlayerProvider::getRyzerPlayer($data['playerName'])) != null) {
                $obj->setRank($data['rank']);
                $obj->setNick($data['nick']);
                $obj->setToggleRank($data['toggleRank']);
                $player->addAttachment(Ryzer::getPlugin())->setPermissions(RankProvider::getPermFormat(RankProvider::getRankPermissions($data['rank'])));
                $player->addAttachment(Ryzer::getPlugin())->setPermissions(RankProvider::getPermFormat(explode(":", $data['permissions'])));

                $cw = false;
                if(Server::getInstance()->getPluginManager()->getPlugin("BedWars") != null) {
                    $cw = BW::isClanWar();
                }
                if(!$cw) {
                    if($data['nick'] == null) {
                        $status = $data['status'];
                        if($data['toggleRank']) {
                            $nametag = str_replace("{player_name}", $player->getName(), RankProvider::getNameTag("Player")); //PLAYER = DEFAULT
                        }else {
                            $nametag = str_replace("{player_name}", $player->getName(), RankProvider::getNameTag($data['rank']));
                        }
                        $nametag = str_replace("&", TextFormat::ESCAPE, $nametag);
                        if($data['clan'] == null || $data['clan'] == "null") {
                            if($status == null) {
                                $player->setNameTag(str_replace("&", TextFormat::ESCAPE, $data['clanColor'])."~"." ".$nametag);
                            }else {
                                $player->setNameTag(str_replace("&", TextFormat::ESCAPE, $data['clanColor'])."~"." ".$nametag."\n".TextFormat::YELLOW."✎ ".$status);
                            }
                        }else {
                            if($status == null) {
                                $player->setNameTag(str_replace("&", TextFormat::ESCAPE, $data['clanColor']).$data['clantag']." ".$nametag);
                            }else {
                                $player->setNameTag(str_replace("&", TextFormat::ESCAPE, $data['clanColor']).$data['clantag']." ".$nametag."\n".TextFormat::YELLOW."✎ ".$status);
                            }
                        }
                        $player->setDisplayName($nametag);
                    }else {
                        Ryzer::getNickProvider()->setNick($player, $obj, $data['nick'], $data['nickSkin']);
                        $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('nick-active', $player->getName(), ['#nick' => $data['nick']]));
                    }

                    if(StaffProvider::isLogin($player->getName())) {
                        Ryzer::getNickProvider()->showAllNicksToTeam($player);
                    }
                }
            }

            //// COINS / PARTICLEMOD\\\\
            if (($obj = RyzerPlayerProvider::getRyzerPlayer($data['playerName'])) != null) {
                $obj->setCoins($data['coins']);
                $obj->setOnlineTime(TextFormat::GOLD.$data['gameTime'][0].TextFormat::AQUA."H ".TextFormat::GOLD.$data['gameTime'][1].TextFormat::AQUA."M");
                $obj->setMoreParticle($data['pm']);
                if($data['clan'] != null && $data['clan'] != "null") {
                    $obj->setClan($data['clan']);
                    $obj->setClanTag($data['clanColor'].$data['clantag']);
                }else {
                    $obj->setClanTag("&g???");
                }
            }

            $ev = new RyZerPlayerAuthEvent($obj, $obj->getLoginData());
            $ev->call();
            
            if(VIPJoinProvider::isVipJoin())
                VIPJoinProvider::check($player);
        }
    }
}