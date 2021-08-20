<?php


namespace baubolp\core\task;


use baubolp\core\provider\MySQLProvider;
use mysqli;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class GameTimeTop5AsyncTask extends AsyncTask
{
    private string $username;
    private array $mysqlData;

    public function __construct(string $username)
    {
        $this->mysqlData = MySQLProvider::getMySQLData();
        $this->username = $username;
    }

    public function onRun()
    {
        $gametimes = [];
        $mysqli = new mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
        $query = $mysqli->query("SELECT * FROM GameTime");
        if($query->num_rows > 0) {
            while ($data = $query->fetch_assoc()) {
                $t = $data['gametime'];
                $i = explode(":", $t);
                if(isset($i[0]) && isset($i[1])) {
                    $hours = $i[0] * 60;
                    $gametimes[$data['playername']] = ['Time' => $hours + $i[1], 'TimeArray' => $i];
                }
            }
        }

        $votes = [];
        $gt = [];
        foreach ($gametimes as $name => $time) {
            //   var_dump($name.":".$time['Time']);
            $votes[$name] = $time['Time'];
            $gt[$name] = $time['TimeArray'];
        }
        $top5 = [];

        for($i = 0; $i < 5; $i++){
            if(count($votes) == 0) {
                $top5[str_repeat("?", $i)] = TextFormat::RED."???";
            }else {
                $top = array_search(max($votes), $votes);
                $top5[$top] = $gt[$top];
                unset($votes[$top]);
            }
        }

        $this->setResult($top5);
        $mysqli->close();
    }

    public function onCompletion(Server $server)
    {
        $top5 = $this->getResult();
        $top_string = TextFormat::GOLD."TOP PLAYTIME";
        $place = 0;
        foreach ($top5 as $name => $gametime) {
            $place++;
            $h = $gametime[0];
            $i = $gametime[1];
            $string = $h." Hours, ".$i." Minutes";
            $top_string .= "\n".TextFormat::RED.$place.". ".TextFormat::AQUA.$name.TextFormat::RED." -> ".TextFormat::AQUA.$string;
        }

        if(($player = $server->getPlayerExact($this->username)))
            $server->getDefaultLevel()->addParticle(new FloatingTextParticle(new Vector3(243, 74.5, 297), $top_string), [$player]);
    }
}