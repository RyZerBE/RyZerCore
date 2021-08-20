<?php


namespace baubolp\core\task;


use baubolp\core\provider\StaffProvider;
use mysqli;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class StaffAsyncTask extends AsyncTask
{

    private array $mysqlData;

    public function __construct(array $mysqlData)
    {
        $this->mysqlData = $mysqlData;
    }

    public function onRun()
    {
        $mysqli = new mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');

        $result = $mysqli->query("SELECT * FROM Staffs");

        $loggedPlayers = [];
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $loggedPlayers[] = $data['playername'];
            }
        }
       /* if($mysqli->close())
            MainLogger::getLogger()->info("MySQL connection successfully closed!");
        else
            MainLogger::getLogger()->info("MySQL connection cannot be closed!");*/

        $this->setResult($loggedPlayers);
    }

    public function onCompletion(Server $server)
    {
        StaffProvider::setLoggedStaffs($this->getResult());
    }
}