<?php


namespace baubolp\core\command;


use baubolp\core\form\JoinMeForm;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class JoinMeCommand extends Command
{

    public function __construct()
    {
        parent::__construct("joinme", "create and join a joinme", "", ['jm']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        Ryzer::getMysqlProvider()->exec(new class($sender->getName()) extends AsyncTask{
            /** @var string */
            private $name;
            private $mysqlData;

            public function __construct(string $playerName)
            {
                $this->name = $playerName;
                $this->mysqlData = MySQLProvider::getMySQLData();
            }

            /**
             * @inheritDoc
             */
            public function onRun()
            {
                $joinMe = [];
                $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $result = $mysqli->query("SELECT * FROM JoinMe");
                if($result->num_rows > 0) {
                    while($data = $result->fetch_assoc()) {
                        $joinMe[$data['playername']] = $data['server'];
                    }
                }else {
                    $joinMe[TextFormat::RED.LanguageProvider::getMessageContainer('no-joinme-exist', $this->name)] = null;
                }
                $mysqli->close();
                $this->setResult($joinMe);
            }

            public function onCompletion(Server $server)
            {
                if(($player = $server->getPlayerExact($this->name)))
                $player->sendForm(new JoinMeForm($player, $this->getResult()));
            }
        });
    }
}