<?php


namespace baubolp\core\player;


use baubolp\core\Ryzer;
use baubolp\core\util\Clan;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RyzerPlayer
{
    public static $os = [0 => '???', 1 => 'Android', 2 => 'iOS', 3 => 'MacOS', 4 => 'FireOS', 5 => 'GearVR', 6 => 'HoloLens', 7 => 'Windows', 8 => 'Windows', 9 => 'Dedicated', 10 => 'Orbis', 11 => 'PS4'];

    /** @var Player */
    private $player;
    /** @var string */
    private $language = "English";
    /** @var \baubolp\core\player\LoginPlayerData  */
    private $loginData;
    /** @var bool  */
    private $muted;
    /** @var array  */
    private $muteData;
    /** @var string  */
    private $rank = "Player";
    /** @var int */
    private $coins = 0;
    /** @var bool */
    private $particleStep = false;
    /** @var null|string */
    private $viewPlayer = null;
    private $lastHit;
    /** @var string  */
    private $lastMessage = "#CoVid19";
    /** @var \pocketmine\entity\Skin */
    private $skin;
    /** @var string|null */
    private $nick;
    /** @var null|Clan */
    private $clan = null;
    /** @var string */
    private $onlineTime;
    /** @var \DateTime */
    private $time;
    /** @var boolean */
    private $toggleRank = false;

    public function __construct(Player $player, LoginPlayerData $loginData, $muted = false, $muteData = [])
    {
        $this->player = $player;
        $this->loginData = $loginData;
        $this->muted = $muted;
        $this->muteData = $muteData;
        $this->lastHit = time();
        $this->skin = $player->getSkin();
        $this->onlineTime = TextFormat::RED."???";
        $this->time = new \DateTime('now');
    }

    /**
     * @return \pocketmine\Player
     */
    public function getPlayer(): \pocketmine\Player
    {
        return $this->player;
    }

    /**
     * @return \baubolp\core\player\LoginPlayerData
     */
    public function getLoginData(): LoginPlayerData
    {
        return $this->loginData;
    }

    /**
     * @return string
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return bool
     */
    public function isMuted(): bool
    {
        return $this->muted;
    }

    /**
     * @return array
     */
    public function getMuteData(): array
    {
        return $this->muteData;
    }

    /**
     * @param array $muteData
     */
    public function setMuteData(array $muteData): void
    {
        $this->muteData = $muteData;
    }

    /**
     * @param bool $muted
     */
    public function setMuted(bool $muted): void
    {
        $this->muted = $muted;
    }

    /**
     * @param mixed $rank
     */
    public function setRank($rank): void
    {
        $this->rank = $rank;
    }

    /**
     * @return string
     */
    public function getRank(): string
    {
        return $this->rank;
    }

    /**
     * @return int
     */
    public function getCoins(): int
    {
        return $this->coins;
    }

    /**
     * @param int $coins
     */
    public function setCoins(int $coins): void
    {
        $this->coins = $coins;
    }

    /**
     * @return string
     */
    public function getDevice(): string
    {
        return self::$os[$this->getLoginData()->getDeviceOs()];
    }

    /**
     * @return bool
     */
    public function isMoreParticle(): bool
    {
        return $this->particleStep;
    }

    /**
     * @param bool $particleStep
     */
    public function setMoreParticle(bool $particleStep): void
    {
        $this->particleStep = $particleStep;
    }

    /**
     * @param string|null $viewPlayer
     */
    public function setViewPlayer(?string $viewPlayer): void
    {
        $this->viewPlayer = $viewPlayer;
    }

    /**
     * @return string|null
     */
    public function getViewPlayer(): ?string
    {
        return $this->viewPlayer;
    }

    /**
     * @return int
     */
    public function getLastHit(): int
    {
        return $this->lastHit;
    }

    public function updateLastHit(): void
    {
        $this->lastHit = time() + 0.8;
    }

    /**
     * @return string
     */
    public function getLastMessage(): string
    {
        return $this->lastMessage;
    }

    /**
     * @param string $lastMessage
     */
    public function setLastMessage(string $lastMessage): void
    {
        $this->lastMessage = $lastMessage;
    }

    /**
     * @return \pocketmine\entity\Skin
     */
    public function getBackupSkin(): \pocketmine\entity\Skin
    {
        return $this->skin;
    }

    /**
     * @param string|null $nick
     */
    public function setNick(?string $nick): void
    {
        $this->nick = $nick;
    }

    /**
     * @return string|null
     */
    public function getNick(): ?string
    {
        return $this->nick;
    }

    /**
     * @return Clan|null
     */
    public function getClan(): ?string
    {
        return $this->clan;
    }

    /**
     * @param Clan|null $clan
     */
    public function setClan(?Clan $clan): void
    {
        $this->clan = $clan;
    }


    /**
     * @return string
     */
    public function getClanTag(): string
    {
        return $this->clan->getClanTag() ?? "&g???";
    }

    /**
     * @param string $onlineTime
     */
    public function setOnlineTime(string $onlineTime): void
    {
        $this->onlineTime = $onlineTime;
    }

    /**
     * @return string
     */
    public function getOnlineTime(): string
    {
        return $this->onlineTime;
    }

    /**
     * @return \DateTime
     */
    public function getTime(): \DateTime
    {
        return $this->time;
    }

    /**
     * @return bool
     */
    public function isToggleRank(): bool
    {
        return $this->toggleRank;
    }

    /**
     * @param bool $toggleRank
     */
    public function setToggleRank(bool $toggleRank): void
    {
        $this->toggleRank = $toggleRank;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        if($this->getNick() != null)
            return $this->getNick();

        return $this->getPlayer()->getName();
    }
}