<?php

namespace ryzerbe\core\util\async;

use ryzerbe\core\RyZerBE;
use Closure;
use Exception;
use mysqli;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\util\Settings;
use function mysqli_connect;
use function uniqid;

class AsyncExecutor {
    use SingletonTrait;

    public array $syncClosures = [];

    public static function submitMySQLAsyncTask(string $database, Closure $function, Closure $completeFunction = null): void{
        if(empty($database)) return;

        $id = uniqid();
        AsyncExecutor::getInstance()->syncClosures[$id] = $completeFunction;
        Server::getInstance()->getAsyncPool()->submitTask(
            new class($function, $id, $database) extends AsyncTask {
                /** @var Closure */
                /* function (mysqli $mysqli) */
                private Closure $function;
                /** @var string */
                private string $id;
                /** @var string */
                private string $database;
                /** @var array */
                private array $mysqlData;

                public function __construct(Closure $function, string $id, string $database){
                    $this->function = $function;
                    $this->id = $id;
                    $this->database = $database;
                    $this->mysqlData = Settings::$mysqlLoginData;
                }

                /**
                 * @inheritDoc
                 */
                public function onRun(){
                    $function = $this->function;
                    $mysqli = new mysqli($this->mysqlData["host"], $this->mysqlData["username"], $this->mysqlData["password"], $this->database);
                    $this->setResult($function($mysqli));
                    if(count($mysqli->error_list) > 0)
                        var_dump($mysqli->error_list);
                    $mysqli->close();
                }

                public function onCompletion(Server $server){
                    try{
                        $completeFunction = AsyncExecutor::getInstance()->syncClosures[$this->id] ?? null;
                        if($completeFunction === null) return;
                        $completeFunction($server, $this->getResult());
                    }catch(Exception $e){
                        $server->getLogger()->error($e->getMessage()."\n".$e->getTraceAsString());
                    }
                }
            });
    }

    public static function submitClosureTask(int $ticks, Closure $closure): void{
        RyZerBE::getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask($closure), $ticks);
    }
}