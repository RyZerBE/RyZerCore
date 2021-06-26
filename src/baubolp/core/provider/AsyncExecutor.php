<?php


namespace baubolp\core\provider;


use baubolp\core\Ryzer;
use Closure;
use Exception;
use mysqli;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class AsyncExecutor
{

    /**
     * @param string $database
     * @param \Closure $function
     * @param \Closure|null $completeFunction
     */
    public static function submitMySQLAsyncTask(string $database, Closure $function, Closure $completeFunction = null)
    {
        if ($function === null) return;
        if(strlen($database) === 0) return;

        Server::getInstance()->getAsyncPool()->submitTask(
            new class($function, $completeFunction, $database) extends AsyncTask {
                /** @var Closure */
                /* function (mysqli $mysqli) */
                private $function;
                /** @var Closure */
                /* function (Server $server, mixed $result)*/
                private $completeFunction;
                /** @var string */
                private $database;
                /** @var array */
                private $mysqlData;

                public function __construct(Closure $function, ?Closure $completeFunction, string $database)
                {
                    $this->function = $function;
                    $this->completeFunction = $completeFunction;
                    $this->database = $database;
                    $this->mysqlData = MySQLProvider::getMySQLData();
                }

                /**
                 * @inheritDoc
                 */
                public function onRun()
                {
                    $function = $this->function;
                    $mysqli = new mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], $this->database);
                    $this->setResult($function($mysqli));
                    if(count($mysqli->error_list) > 0)
                        var_dump($mysqli->error_list);
                    $mysqli->close();
                }

                public function onCompletion(Server $server)
                {
                    if ($this->completeFunction === null) return;

                    try {
                        $completeFunction = $this->completeFunction;
                        $completeFunction($server, $this->getResult());
                    }catch (Exception $e) {
                        $server->getLogger()->error($e->getMessage()."\n". $e->getTraceAsString());
                    }
                }
            });
    }

    public static function submitClosureTask(int $ticks, Closure $closure)
    {
        Ryzer::getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask($closure), $ticks);
    }
}