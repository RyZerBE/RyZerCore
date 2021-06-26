<?php


namespace baubolp\core\command;


use baubolp\core\form\VerifyForm;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\provider\VerifyProvider;
use baubolp\core\Ryzer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class VerifyCommand extends Command
{

    public function __construct()
    {
        parent::__construct('verify', "verify you with our discord(discord.ryzer.be)", "", ['verification']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        Server::getInstance()->getAsyncPool()->submitTask(new class($sender->getName(), MySQLProvider::getMySQLData()) extends AsyncTask{

            private $playerName;
            private $mysqlData;

            public function __construct(string $playerName, array $mysqlData)
            {
                $this->playerName = $playerName;
                $this->mysqlData = $mysqlData;
            }

            public function onRun()
            {
                $mysql = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $name = $this->playerName;

                $result = $mysql->query("SELECT * FROM Verify WHERE playername='$name'");
                $resultArray = ['token' => null, 'isVerified' => false];
                if($result->num_rows > 0) {
                    while($data = $result->fetch_assoc()) {
                        $resultArray['token'] = $data['token'];
                        $resultArray['isVerified'] = ($data['isverified'] != "false") ? true : false;
                    }
                }else {
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $charactersLength = strlen($characters);
                    $token = '';
                    for ($i = 0; $i < 5; $i++) {
                        $token .= $characters[rand(0, $charactersLength - 1)];
                    }
                    $mysql->query("INSERT INTO `Verify`(`playername`, `token`, `isverified`) VALUES ('$name', '$token', 'false')");
                    $resultArray = ['token' => null, 'isVerified' => false];
                }
                $this->setResult($resultArray);
                $mysql->close();
            }

            public function onCompletion(Server $server)
            {
                $result = $this->getResult();
                if(($player = $server->getPlayerExact($this->playerName)) != null) {
                    if($result['token'] == null) {
                        $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('token-created', $player->getName()));
                        return;
                    }

                    $player->sendForm(new VerifyForm($result['token'], $result['isVerified']));
                }
            }
        });
    }
}