<?php


namespace baubolp\core\provider;


use baubolp\core\player\RyzerPlayer;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\Ryzer;
use BauboLP\NPC\NPC;
use pocketmine\entity\DataPropertyManager;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;

class NickProvider
{
    /** @var array  */
    private $nickNames = [];

    const DEFAULT = [
        "JoelTV",
        "BleZi_YT",
        "elvix",
        "GlitchTrap12",
        "Deoxity",
        "Frolow",
        "MortalStrk",
        "JESAIPA",
        "Kqsper1505",
        "Isqqc_LP",
        "RezznoMc",
        "Sawolth",
        "smolsnowy"
    ];

    public function __construct()
    {
        if(!file_exists("/root/RyzerCloud/data/Nick/nicks.yml")) {
            $c = new Config("/root/RyzerCloud/data/Nick/nicks.yml", 2);
            $c->set("Nicks", []);
            $c->save();
        }
        $c = new Config("/root/RyzerCloud/data/Nick/nicks.yml", 2);

        foreach (self::DEFAULT as $nick)
            $this->nickNames[] = $nick;

        foreach ($c->get("Nicks") as $nick) {
            $this->nickNames[] = (string)$nick;
        }
        MainLogger::getLogger()->info("NickNames loaded!");
    }

    /**
     * @return array
     */
    public function getNickNames(): array
    {
        return $this->nickNames;
    }

    /**
     * @return string
     */
    public function getRandomNick(): string
    {
        return $this->getNickNames()[array_rand($this->getNickNames())];
    }

    /**
     * @return string
     */
    public function getRandomNickSkin(): string
    {
        $skins = [];
        foreach (scandir("/root/RyzerCloud/data/Nick") as $skin) {
            if (explode(".", $skin)[1] === "png") {
                $skins[] = explode(".", $skin)[0];
            }
        }

        $index = rand(1, count($skins)) - 1;
        $skin = $skins[$index];
        return $skin;
    }

    protected function getSkinBytes(string $pngFile)
    {
        $path = "/root/RyzerCloud/data/Nick/".$pngFile.".png";
        $img = @imagecreatefrompng($path);
        $bytes = '';

        $l = (int)@getimagesize($path)[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; ++$x) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);

        return $bytes;
    }

    /**
     * @param \pocketmine\Player $player
     * @param string $pngFile
     * @return \pocketmine\entity\Skin
     */
    public function pngToSkin(Player $player, string $pngFile)
    {
        $bytes = self::getSkinBytes($pngFile);
        return new Skin($player->getSkin()->getSkinId(), $bytes, "", $player->getSkin()->getGeometryName());
    }

    public function setNick(Player $sender, RyzerPlayer $obj, string $name = "", string $skin = "")
    {
        if($name == "")
        $nickName = Ryzer::getNickProvider()->getRandomNick();
        else
            $nickName = $name;

        if($skin == "")
        $skinName = Ryzer::getNickProvider()->getRandomNickSkin();
        else
            $skinName = $skin;
        
        $nickSkin = Ryzer::getNickProvider()->pngToSkin($sender, $skinName);

        $nametag = str_replace("{player_name}", $nickName, RankProvider::getNameTag("Player"));
        $nametag = str_replace("&", TextFormat::ESCAPE, $nametag);

        $obj->setNick($nickName);
        $sender->setNameTag(TextFormat::YELLOW."~ ".$nametag);
        $sender->setDisplayName($nametag);
        $sender->setSkin($nickSkin);
        $sender->sendSkin();
        Ryzer::getNickProvider()->showNickToTeam($obj->getPlayer());
        Server::getInstance()->updatePlayerListData($sender->getUniqueId(), $sender->getId(), $sender->getDisplayName(), $nickSkin, $sender->getXuid());
        Ryzer::getMysqlProvider()->exec(new class($sender->getName(), $nickName, $skinName) extends AsyncTask{
            /** @var string  */
            private $playerName;
            /** @var string  */
            private $skinMae;
            /** @var string  */
            private $nickName;
            /** @var array  */
            private $mysqlData;

            /**
             *  constructor.
             *
             * @param string $playerName
             * @param string $nickName
             * @param string $skinname
             */
            public function __construct(string $playerName, string $nickName, string $skinname)
            {
                $this->playerName = $playerName;
                $this->skinMae = $skinname;
                $this->nickName = $nickName;
                $this->mysqlData = MySQLProvider::getMySQLData();
            }

            /**
             * @inheritDoc
             */
            public function onRun()
            {
                $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');

                $playerName = $this->playerName;
                $nickName = $this->nickName;
                $skinName = $this->skinMae;
                $mysqli->query("INSERT INTO `Nick`(`playername`, `nick`, `skin`) VALUES ('$playerName', '$nickName', '$skinName')");
                $mysqli->close();
            }
        });
    }

    public function removeNick(Player $sender, RyzerPlayer $obj)
    {
        $obj->setNick(null);
        $sender->setSkin($obj->getBackupSkin());
        $sender->sendSkin();
        $nametag = str_replace("{player_name}", $sender->getName(), RankProvider::getNameTag($obj->getRank()));
        $nametag = str_replace("&", TextFormat::ESCAPE, $nametag);
        $sender->setNameTag($nametag);
        $sender->setDisplayName(TextFormat::YELLOW."~ ".$nametag);
        Server::getInstance()->updatePlayerListData($sender->getUniqueId(), $sender->getId(), $sender->getDisplayName(), $obj->getBackupSkin(), $sender->getXuid());
        Ryzer::getMysqlProvider()->exec(new class($sender->getName()) extends AsyncTask{
            /** @var string  */
            private $playerName;
            /** @var array  */
            private $mysqlData;

            /**
             *  constructor.
             *
             * @param string $playerName
             */
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
                $mysqli = new \mysqli($this->mysqlData['host'] . ':3306', $this->mysqlData['user'], $this->mysqlData['password'], 'RyzerCore');

                $playerName = $this->playerName;
                $mysqli->query("DELETE FROM Nick WHERE playername='$playerName'");
                $mysqli->close();
            }
        });
    }

    /**
     * @param \pocketmine\Player $staff
     */
    public function showAllNicksToTeam(Player $staff)
    {
        foreach (RyzerPlayerProvider::getRyzerPlayers() as $player) {
            if($player->getNick() != null) {
                Ryzer::renameEntity($player->getPlayer()->getId(), $player->getPlayer()->getNameTag()." ".TextFormat::DARK_GRAY."[".TextFormat::GREEN.$player->getPlayer()->getName().TextFormat::DARK_GRAY."]", "", [$staff]);
            }
        }
    }

    /**
     * @param \pocketmine\Player $nickedPlayer
     */
    public function showNickToTeam(Player $nickedPlayer)
    {
        foreach (StaffProvider::getLoggedStaff() as $staff) {
          if(($staffPlayer = $nickedPlayer->getServer()->getPlayerExact($staff)) != null)
            Ryzer::renameEntity($nickedPlayer->getId(), $nickedPlayer->getNameTag()." ".TextFormat::DARK_GRAY."[".TextFormat::GREEN.$nickedPlayer->getName().TextFormat::DARK_GRAY."]", "", [$staffPlayer]);
        }
    }
}