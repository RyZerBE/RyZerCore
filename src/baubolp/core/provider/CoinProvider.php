<?php


namespace baubolp\core\provider;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class CoinProvider
{
    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function addCoins(string $playerName, int $coins)
    {
        Server::getInstance()->getAsyncPool()->submitTask(new class($playerName, $coins, MySQLProvider::getMySQLData()) extends AsyncTask{
           /** @var string  */
            private $playerName;
           /** @var int  */
           private $coins;
           /** @var array  */
           private $mysqlData;

            public function __construct(string $playerName, int $coins, array $mysqlData)
            {
                $this->coins = $coins;
                $this->playerName = $playerName;
                $this->mysqlData = $mysqlData;
            }

            public function onRun()
            {
                $coins = $this->coins;
                $playerName = $this->playerName;
                $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $mysqli->query("UPDATE Coins SET coins=coins+'$coins' WHERE playername='$playerName'");
                $mysqli->close();
                $this->setResult(true);
            }

            public function onCompletion(Server $server)
            {
                if($this->getResult()) {
                    if(($player = Server::getInstance()->getPlayerExact($this->playerName)) != null) {
                        $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('added-coins', $player->getName(), ['#coins' => $this->coins." Coins"]));
                    }
                }
            }
        });
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
            $obj->setCoins($obj->getCoins() + $coins);
        }
    }

    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function removeCoins(string $playerName, int $coins)
    {
        Server::getInstance()->getAsyncPool()->submitTask(new class($playerName, $coins, MySQLProvider::getMySQLData()) extends AsyncTask{
            /** @var string  */
            private $playerName;
            /** @var int  */
            private $coins;
            /** @var array  */
            private $mysqlData;

            public function __construct(string $playerName, int $coins, array $mysqlData)
            {
                $this->coins = $coins;
                $this->playerName = $playerName;
                $this->mysqlData = $mysqlData;
            }

            public function onRun()
            {
                $coins = $this->coins;
                $playerName = $this->playerName;
                $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $mysqli->query("UPDATE Coins SET coins=coins-'$coins' WHERE playername='$playerName'");
                $mysqli->close();
                $this->setResult(true);
            }

            public function onCompletion(Server $server)
            {
                if($this->getResult()) {
                    if(($player = Server::getInstance()->getPlayerExact($this->playerName)) != null) {
                        $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('removed-coins', $player->getName(), ['#coins' => $this->coins." Coins"]));
                    }
                }
            }
        });

        if(($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
            $obj->setCoins($obj->getCoins() - $coins);
        }
    }

    /**
     * @param string $playerName
     * @param int $coins
     */
    public static function setCoins(string $playerName, int $coins)
    {
        Server::getInstance()->getAsyncPool()->submitTask(new class($playerName, $coins, MySQLProvider::getMySQLData()) extends AsyncTask{
            /** @var string  */
            private $playerName;
            /** @var int  */
            private $coins;
            /** @var array  */
            private $mysqlData;

            public function __construct(string $playerName, int $coins, array $mysqlData)
            {
                $this->coins = $coins;
                $this->playerName = $playerName;
                $this->mysqlData = $mysqlData;
            }

            public function onRun()
            {
                $coins = $this->coins;
                $playerName = $this->playerName;
                $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                $mysqli->query("UPDATE Coins SET coins='$coins' WHERE playername='$playerName'");
                $mysqli->close();
                $this->setResult(true);
            }

            public function onCompletion(Server $server)
            {
                if($this->getResult()) {
                    if(($player = Server::getInstance()->getPlayerExact($this->playerName)) != null) {
                        $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('set-coins', $player->getName(), ['#coins' => $this->coins." Coins"]));
                    }
                }
            }
        });
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
            $obj->setCoins($coins);
        }
    }
}