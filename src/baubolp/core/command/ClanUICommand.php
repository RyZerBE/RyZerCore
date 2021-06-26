<?php


namespace baubolp\core\command;


use BauboLP\Cloud\CloudBridge;
use baubolp\core\form\clan\ChooseEloOrFunForm;
use baubolp\core\form\clan\ClanColorForm;
use baubolp\core\form\clan\ClanMainMenu;
use baubolp\core\form\clan\ClanMemberManageForm;
use baubolp\core\form\clan\ClanMemberOptionForm;
use baubolp\core\form\clan\ClanTop10Form;
use baubolp\core\form\clan\CreateClanForm;
use baubolp\core\form\clan\InvitePlayerForm;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ClanUICommand extends Command
{

    public function __construct()
    {
        parent::__construct("cui", "Create and manage your clan", "", ['clanui']);
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        Ryzer::getMysqlProvider()->exec(new class($sender->getName()) extends AsyncTask{
            /** @var string */
            private $playerName;
            private $mysqlData;

            public function __construct(string $playerName)
            {
                $this->mysqlData = MySQLProvider::getMySQLData();
                $this->playerName = $playerName;
            }

            /**
             * @inheritDoc
             */
            public function onRun()
            {
                $playerName = $this->playerName;
                $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'Clans');

                $pData = [];
                $result = $mysqli->query("SELECT clan,role FROM ClanPlayers WHERE playername='$playerName'");
                if($result->num_rows > 0){
                    while($data = $result->fetch_assoc()) {
                        $pData['clan'] = $data['clan'];
                        $pData['role'] = $data['role'];
                    }
                }

                $clanName = $pData['clan'];

                $result = $mysqli->query("SELECT members,clantag FROM Clans WHERE clanname='$clanName'");
                if($result->num_rows > 0){
                    while($data = $result->fetch_assoc()) {
                        if($data['members'] != "") {
                            $pData['members'] = explode(":", $data['members']);
                        }else {
                            $pData['members'] = [];
                        }

                        $pData['clantag'] = $data['clantag'];
                    }
                }else {
                    $pData['members'] = [];
                    $pData['clantag'] = "";
                }

                $pData['playerName'] = $playerName;
                $this->setResult($pData);
                $mysqli->close();
            }

            public function onCompletion(Server $server)
            {
                $data = $this->getResult();
                $options = [];
                $onSubmit = function (Player $player, int $selectedOption): void{};
                if($data['clan'] != null && $data['clan'] != "null") {
                    if($data['role'] == 0) {
                        $options[] = new MenuOption(TextFormat::GOLD."Top 10 Clans");
                        $options[] = new MenuOption(TextFormat::YELLOW."Clan Info");
                        $options[] = new MenuOption(TextFormat::RED."Clan leave");
                        $pName = $data['playerName'];
                        $onSubmit = function (Player $player, int $selectedOption) use ($pName): void{
                            switch ($selectedOption) {
                                case 0:
                                    Ryzer::getAsyncConnection()->executeQuery("SELECT clanname, elo FROM Clans ORDER BY elo DESC LIMIT 10", "Clans", function (\mysqli_result $result) use ($pName) {
                                        $return = [];
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $return[] = ["clanname" => $row["clanname"], "elo" => $row["elo"]];
                                            }
                                        }
                                        return $return;
                                    }, function ($r, $e){}, [], function (Server $server, array $array, $extra_data) use ($pName){
                                        $options = [];
                                        $place = 0;
                                        foreach ($array as $data) {
                                            $place++;
                                            $options[] = new MenuOption(TextFormat::GRAY."#".TextFormat::RED.$place." ".TextFormat::YELLOW.$data['clanname']."\n".TextFormat::RED.$data['elo']." Elo");
                                        }

                                        if(($player = $server->getPlayerExact($pName)) != null) {
                                            $player->sendForm(new ClanTop10Form($options));
                                        }
                                    });
                                    break;
                                case 1:
                                   CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan info");
                                    break;
                                case 2:
                                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan leave");
                                    break;
                            }
                        };
                    }else if($data['role'] == 1) {
                        $options[] = new MenuOption(TextFormat::GOLD.TextFormat::BOLD."Join ClanWar Queue");
                        $options[] = new MenuOption(TextFormat::GREEN."Top 10 Clans");
                        $options[] = new MenuOption(TextFormat::AQUA."Clan Info");
                        $options[] = new MenuOption(TextFormat::GREEN."Clan-Members manage");
                        $options[] = new MenuOption(TextFormat::GREEN."Invite Player");
                        $options[] = new MenuOption(TextFormat::RED."Clan leave");
                        $options[] = new MenuOption(TextFormat::DARK_PURPLE."Clan Party");
                        $onSubmit = function (Player $player, int $selectedOption) use ($data): void{
                            switch ($selectedOption) {
                                case 0:
                                    $player->sendForm(new ChooseEloOrFunForm());
                                    break;
                                case 1:
                                    $pName = $player->getName();
                                    Ryzer::getAsyncConnection()->executeQuery("SELECT clanname, elo FROM Clans ORDER BY elo DESC LIMIT 10", "Clans", function (\mysqli_result $result) use ($pName) {
                                        $return = [];
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $return[] = ["clanname" => $row["clanname"], "elo" => $row["elo"]];
                                            }
                                        }
                                        return $return;
                                    }, function ($r, $e){}, [], function (Server $server, array $array, $extra_data) use ($pName){
                                        $options = [];
                                        $place = 0;
                                        foreach ($array as $data) {
                                            $place++;
                                            $options[] = new MenuOption(TextFormat::GRAY."#".TextFormat::RED.$place." ".TextFormat::YELLOW.$data['clanname']."\n".TextFormat::RED.$data['elo']." Elo");
                                        }

                                        if(($player = $server->getPlayerExact($pName)) != null) {
                                            $player->sendForm(new ClanTop10Form($options));
                                        }
                                    });
                                    break;
                                case 2:
                                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan info");
                                    break;
                                case 3:
                                    $player->sendForm(new ClanMemberManageForm($data['members'], false));
                                    break;
                                case 4:
                                    $player->sendForm(new InvitePlayerForm());
                                    break;
                                case 5:
                                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan leave");
                                    break;
                                case 6:
                                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan party");
                                    break;
                            }
                        };
                    }else if($data['role'] > 1) {
                        $options[] = new MenuOption(TextFormat::GOLD.TextFormat::BOLD."Join ClanWar Queue");
                        $options[] = new MenuOption(TextFormat::GREEN."Top 10 Clans");
                        $options[] = new MenuOption(TextFormat::AQUA."Clan Info");
                        $options[] = new MenuOption(TextFormat::AQUA."Color");
                        $options[] = new MenuOption(TextFormat::GREEN."Clan-Members manage");
                        $options[] = new MenuOption(TextFormat::GREEN."Invite Player");
                        $options[] = new MenuOption(TextFormat::RED."Delete your Clan");
                        $options[] = new MenuOption(TextFormat::DARK_PURPLE."Clan Party");

                        $onSubmit = function (Player $player, int $selectedOption) use ($data): void{
                            switch ($selectedOption) {
                                case 0:
                                    $player->sendForm(new ChooseEloOrFunForm());
                                    break;
                                case 1:
                                    $pName = $player->getName();
                                    Ryzer::getAsyncConnection()->executeQuery("SELECT clanname, elo FROM Clans ORDER BY elo DESC LIMIT 10", "Clans", function (\mysqli_result $result) use ($pName) {
                                        $return = [];
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $return[] = ["clanname" => $row["clanname"], "elo" => $row["elo"]];
                                            }
                                        }
                                        return $return;
                                    }, function ($r, $e){}, [], function (Server $server, array $array, $extra_data) use ($pName){
                                        $options = [];
                                        $place = 0;
                                        foreach ($array as $data) {
                                            $place++;
                                            $options[] = new MenuOption(TextFormat::GRAY."#".TextFormat::RED.$place." ".TextFormat::YELLOW.$data['clanname']."\n".TextFormat::RED.$data['elo']." Elo");
                                        }

                                        if(($player = $server->getPlayerExact($pName)) != null) {
                                            $player->sendForm(new ClanTop10Form($options));
                                        }
                                    });
                                    break;
                                case 2:
                                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan info");
                                    break;
                                case 3:
                                    $player->sendForm(new ClanColorForm($data['clantag']));
                                    break;
                                case 4:
                                    $player->sendForm(new ClanMemberManageForm($data['members'], true));
                                    break;
                                case 5:
                                    $player->sendForm(new InvitePlayerForm());
                                    break;
                                case 6:
                                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan delete");
                                    break;
                                case 7:
                                    CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan party");
                                    break;
                            }
                        };
                    }
                }else {
                    $options[] = new MenuOption(TextFormat::DARK_AQUA."Create Clan");
                    $options[] = new MenuOption(TextFormat::GOLD."Invites");

                    $onSubmit = function (Player $player, int $selectedOption) use ($data): void{
                        switch ($selectedOption) {
                            case 0:
                                $player->sendForm(new CreateClanForm());
                                break;
                            case 1:
                                CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan requests");
                                $player->sendMessage(Ryzer::PREFIX.TextFormat::GREEN."/clan accept <ClanName> ".TextFormat::DARK_GRAY."| ".TextFormat::RED."/clan deny <ClanName>");
                                break;
                        }
                    };
                }

                if(($player = $server->getPlayerExact($this->playerName)) != null) {
                    $player->sendForm(new ClanMainMenu($options, $onSubmit));
                }
            }
        });
    }
}