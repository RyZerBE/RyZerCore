<?php

namespace baubolp\core\command;

use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ToggleRankCommand extends Command
{

    public function __construct()
    {
        parent::__construct("togglerank", "hide your rank", "", ['hiderank']);
        $this->setPermission("core.togglerank");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        if(($obj = RyzerPlayerProvider::getRyzerPlayer($sender->getName())) != null) {
            if($obj->isToggleRank()) {
                Ryzer::getMysqlProvider()->exec(new class($sender->getName()) extends AsyncTask{

                    private $playerName;
                    private $mysqlData;

                    public function __construct(string $playerName)
                    {
                        $this->playerName = $playerName;
                        $this->mysqlData = MySQLProvider::getMySQLData();
                    }

                    /**
                     * @inheritDoc
                     */
                    public function onRun()
                    {
                        $mysqli = new \mysqli($this->mysqlData['host'] . ":3306", $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                        $name = $this->playerName;
                        $mysqli->query("DELETE FROM `ToggleRank` WHERE playername='$name'");
                        $mysqli->close();
                    }

                    public function onCompletion(Server $server)
                    {
                        if (($obj = RyzerPlayerProvider::getRyzerPlayer($this->playerName)) != null) {
                            $obj->setToggleRank(false);
                            $obj->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('rank-now-visible', $obj->getPlayer()->getName(), ['#rank' => $obj->getRank()]));
                        }
                    }
                });
            }else {
                Ryzer::getMysqlProvider()->exec(new class($sender->getName()) extends AsyncTask{

                    private $playerName;
                    private $mysqlData;

                    public function __construct(string $playerName)
                    {
                        $this->playerName = $playerName;
                        $this->mysqlData = MySQLProvider::getMySQLData();
                    }

                    /**
                     * @inheritDoc
                     */
                    public function onRun()
                    {
                        $mysqli = new \mysqli($this->mysqlData['host'] . ":3306", $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                        $name = $this->playerName;
                        $mysqli->query("INSERT INTO `ToggleRank`(`playername`) VALUES ('$name')");
                        $mysqli->close();
                    }

                    public function onCompletion(Server $server)
                    {
                        if (($obj = RyzerPlayerProvider::getRyzerPlayer($this->playerName)) != null) {
                            $obj->setToggleRank(true);
                            $obj->getPlayer()->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('rank-hidden', $obj->getPlayer()->getName()));
                        }
                    }
                });
            }
        }
    }
}