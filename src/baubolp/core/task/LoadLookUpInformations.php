<?php


namespace baubolp\core\task;


use baubolp\core\form\LookForm;
use baubolp\core\provider\ModerationProvider;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\provider\ReportProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuOption;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class LoadLookUpInformations extends AsyncTask
{
    /** @var string  */
    private $playerName;
    /** @var array */
    private $mysqlData;
    /** @var string */
    private $sender;

    public function __construct(string $playerName, string $sender)
    {
        $this->playerName = $playerName;
        $this->sender = $sender;
        $this->mysqlData = MySQLProvider::getMySQLData();
    }

    public function onRun()
    {
        $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
        $playerData = [];
        $playerName = $this->playerName;

        ////LANGUAGE \\\\
        $result = $mysqli->query("SELECT selected_language FROM PlayerLanguage WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            while ($data = $result->fetch_assoc()) {
                $playerData['language'] = $data['selected_language'];
            }
        }else {
            $playerData['error'] = TextFormat::RED."ERROR: Es existiert KEIN registriertes Konto mit dem Namen ".TextFormat::AQUA.$this->playerName;
            $this->setResult($playerData);
            return;
        }

        $result = $mysqli->query("SELECT rankname,permissions FROM PlayerPerms WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['rank'] = $data['rankname'];
                $playerData['permissions'] = explode(":", $data['permissions']);
            }
        }

        $result = $mysqli->query("SELECT coins FROM Coins WHERE playername='$playerName'");
        if ($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['coins'] = $data['coins'];
            }
        }

        $clanDB = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'Clans');
        $result = $clanDB->query("SELECT clan FROM ClanPlayers WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['clan'] = $data['clan'];
            }
        }else {
            $playerData['clan'] = "Clanless";
        }

        $result = $mysqli->query("SELECT * FROM Nick WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['nick'] = $data['nick'];
            }
        }else {
            $playerData['nick'] = "/";
        }

        $result = $mysqli->query("SELECT ip,clientid,clientmodel,accounts,device,firstjoin FROM PlayerData WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['ip'] = $data['ip'];
                $playerData['clientid'] = $data['clientid'];
                $playerData['clientmodel'] = $data['clientmodel'];
                $playerData['accounts'] = explode(":", $data['accounts']);
                $playerData['firstjoin'] = $data['firstjoin'];
                $playerData['device'] = $data['device'];
            }
        }

        $result = $mysqli->query("SELECT * FROM Verify WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['token'] = $data['token'];
            }
        }else {
            $playerData['token'] = "/";
        }

        $result = $mysqli->query("SELECT gametime FROM GameTime WHERE playername='$playerName'");
        if($result->num_rows > 0) {
            while($data = $result->fetch_assoc()) {
                $playerData['gametime'] = explode(":", $data['gametime']);
            }
        }else {
            $playerData['gametime'] = [0, 0];
        }

        $this->setResult($playerData);

        $mysqli->close();
        $clanDB->close();
    }

    public function onCompletion(Server $server)
    {
      $data = $this->getResult();

      if(($player = $server->getPlayerExact($this->sender)) != null) {
          if(isset($data['error'])) {
              $player->sendMessage(Ryzer::PREFIX.$data['error']);
              $player->playSound("note.bass", 5.0, 1.0, [$player]);
              return;
          }
          $countOfReports = 0;

          foreach (ReportProvider::getArchive() as $archive) {
              if($archive['badPlayer'] == $this->playerName)
                  $countOfReports++;
          }
          $player->sendForm(new LookForm($player, $data['rank'], $data['coins'], $data['token'], $data['nick'], $data['language'], $data['ip'],
              $data['clientid'], $data['clientmodel'], $countOfReports, $data['permissions'], $data['clan'], $data['device'], $data['accounts'], ($data['firstjoin'] == "") ? "???" : ModerationProvider::formatGermanDate($data['firstjoin']), $data['gametime']));
      }
    }
}