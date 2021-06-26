<?php


namespace baubolp\core\form;


use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\MySQLProvider;
use baubolp\core\Ryzer;
use pocketmine\form\CustomForm;
use pocketmine\form\CustomFormResponse;
use pocketmine\form\element\StepSlider;
use pocketmine\form\element\Toggle;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ParticleModForm extends CustomForm
{

    public function __construct(string $playerName)
    {
        if(($obj = RyzerPlayerProvider::getRyzerPlayer($playerName)) != null) {
            $elements = [new Toggle("ParticleMod", LanguageProvider::getMessageContainer('more-particle-toggle', $playerName), $obj->isMoreParticle())];
        }else {
            $elements = [new Toggle("ParticleMod", LanguageProvider::getMessageContainer('more-particle-toggle', $playerName), false)];
        }
        parent::__construct(Ryzer::PREFIX.TextFormat::YELLOW.'More Particle', $elements, function (Player $player, CustomFormResponse $response): void{
              if(($obj = RyzerPlayerProvider::getRyzerPlayer($player->getName())) != null) {
                  $pm = $response->getBool($this->getElement(0)->getName());
                  $obj->setMoreParticle($pm);
                  Server::getInstance()->getAsyncPool()->submitTask(new class($player->getName(), $pm, MySQLProvider::getMySQLData()) extends AsyncTask{

                      /** @var string  */
                      private $playerName;
                      /** @var bool  */
                      private $pm;
                      /** @var array  */
                      private $mysqlData;

                      public function __construct(string $playerName, bool $pm, array $data)
                      {
                          $this->playerName = $playerName;
                          $this->pm = $pm;
                          $this->mysqlData = $data;
                      }

                      public function onRun()
                      {
                          $pm = (int)$this->pm; //int because mysql
                          $playerName = $this->playerName;
                          $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');
                          $mysqli->query("UPDATE ParticleMod SET pm='$pm' WHERE playername='$playerName'");
                          $mysqli->close();
                      }

                      public function onCompletion(Server $server)
                      {
                          if(($player = $server->getPlayerExact($this->playerName)) != null) {
                              $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('settings-updated', $player->getName()));
                          }
                      }
                  });
              }
        });
    }
}