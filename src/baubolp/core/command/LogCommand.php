<?php


namespace baubolp\core\command;


use baubolp\core\provider\ModerationProvider;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

class LogCommand extends Command
{

    public function __construct()
    {
        parent::__construct('log', "Get the punishment log of a player", "", ['']);
        $this->setPermission("core.log");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender)) return;

        if(empty($args[0])) {
            $sender->sendMessage(Ryzer::PREFIX.TextFormat::RED."/log <Player>");
            return;
        }

        $playerName = $args[0];
        $senderName = $sender->getName();

        Server::getInstance()->getAsyncPool()->submitTask(new class($playerName, $senderName, MySQLProvider::getMySQLData()) extends AsyncTask{

            private $playerName;
            private $mysqlData;
            private $senderName;

            public function __construct(string $playerName, string $senderName, array $mysqlData)
            {
                $this->playerName = $playerName;
                $this->mysqlData = $mysqlData;
                $this->senderName = $senderName;
            }

            public function onRun()
            {
                $mysqli = new \mysqli($this->mysqlData['host'].":3306", $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');

                $log = Ryzer::PREFIX.TextFormat::RED."Straflog über ".$this->playerName.TextFormat::GRAY.":\n";

                $playerName = $this->playerName;
                $result = $mysqli->query("SELECT log,warns,unbanlog FROM PlayerModeration WHERE playername='$playerName'");
                if($result->num_rows > 0) {
                    while($data = $result->fetch_assoc()) {
                        $stringLog = $data['log'];
                        $warnLog = $data['warns'];
                        $unbanlog = $data['unbanlog'];
                        if($stringLog == "") {
                            $log = Ryzer::PREFIX."Der Spieler wurde noch nie bestraft.";
                        }else {
                            foreach (explode("*", $stringLog) as $logHistory) {
                                if($logHistory != "" && $logHistory != null) {
                                    $logData = explode(",", $logHistory);
                                    $reason = $logData[0];
                                    $staff = $logData[1];
                                    $banid = $logData[3];
                                    $duration = ModerationProvider::formatGermanDate($logData[2]);

                                    $log .= Ryzer::PREFIX.TextFormat::GRAY."Der Spieler wurde für ".TextFormat::RED.$reason.TextFormat::GRAY." bis zum ".TextFormat::RED.$duration.TextFormat::GRAY." von dem Staff ".TextFormat::YELLOW.$staff.TextFormat::GRAY." bestraft. (".TextFormat::YELLOW.$banid.TextFormat::GRAY.")\n";
                                }
                            }
                            foreach (explode("*", $warnLog) as $warnHistory) {
                                if($warnHistory != "" && $warnHistory != null) {
                                    $logData = explode(",", $warnHistory);
                                    $reason = $logData[0];
                                    $staff = $logData[1];
                                    $duration = ModerationProvider::formatGermanDate($logData[2]);

                                    $log .= Ryzer::PREFIX.TextFormat::GRAY."Der Spieler wurde am ".TextFormat::YELLOW.$duration.TextFormat::GRAY." für den Grund ".TextFormat::RED.$reason.TextFormat::GRAY." von dem Staff ".TextFormat::YELLOW.$staff.TextFormat::GRAY." verwarnt.\n";
                                }
                            }
                            foreach (explode("*", $unbanlog) as $unbanLog) {
                                if($unbanLog != "" && $unbanLog != null) {
                                    $logData = explode(",", $unbanLog);
                                    $reason = $logData[0];
                                    $staff = $logData[1];
                                    $isBan = $logData[3];
                                    $duration = ModerationProvider::formatGermanDate($logData[2]);
                                    if($isBan) {
                                        $log .= Ryzer::PREFIX.TextFormat::GRAY."Der Spieler wurde am ".TextFormat::YELLOW.$duration.TextFormat::GRAY." für den Grund ".TextFormat::RED.$reason.TextFormat::GRAY." von dem Staff ".TextFormat::YELLOW.$staff.TextFormat::GRAY." entbannt.\n";
                                    } else {
                                        $log .= Ryzer::PREFIX.TextFormat::GRAY."Der Spieler wurde am ".TextFormat::YELLOW.$duration.TextFormat::GRAY." für den Grund ".TextFormat::RED.$reason.TextFormat::GRAY." von dem Staff ".TextFormat::YELLOW.$staff.TextFormat::GRAY." entmutet.\n";
                                    }
                                }
                            }
                        }
                    }
                }
                $this->setResult($log);
            }

            public function onCompletion(Server $server)
            {
                if(($obj = Server::getInstance()->getPlayer($this->senderName)) != null) {
                    if($this->getResult() != null)
                    $obj->sendMessage($this->getResult());
                }
            }
        });
    }
}