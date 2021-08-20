<?php


namespace baubolp\core\player;


use baubolp\core\provider\RankProvider;
use baubolp\core\util\Clan;
use DateTime;
use pocketmine\entity\Skin;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function str_replace;

class RyzerPlayer
{
    /** @var string[]  */
    public static array $os = [0 => '???', 1 => 'Android', 2 => 'iOS', 3 => 'MacOS', 4 => 'FireOS', 5 => 'GearVR', 6 => 'HoloLens', 7 => 'Windows', 8 => 'Windows', 9 => 'Dedicated', 10 => 'Orbis', 11 => 'PS4'];

    /** @var Player */
    private Player $player;
    /** @var string|null */
    private ?string $language = "English";
    /** @var LoginPlayerData */
    private LoginPlayerData $loginData;
    /** @var bool  */
    private bool $muted;
    /** @var array  */
    private array $muteData;
    /** @var string  */
    private string $rank = "Player";
    /** @var int */
    private int $coins = 0;
    /** @var bool */
    private bool $particleStep = false;
    /** @var null|string */
    private ?string $viewPlayer = null;
    /** @var float  */
    private float $lastHit;
    /** @var string  */
    private string $lastMessage = "#CoVid19";
    /** @var Skin */
    private Skin $skin;
    /** @var string|null */
    private ?string $nick;
    /** @var null|Clan */
    private ?Clan $clan = null;
    /** @var string */
    private string $onlineTime;
    /** @var DateTime */
    private DateTime $time;
    /** @var boolean */
    private bool $toggleRank = false;
    /** @var NetworkLevel|null  */
    private ?NetworkLevel $networkLevel = null;

    /**
     * RyzerPlayer constructor.
     * @param Player $player
     * @param LoginPlayerData $loginData
     * @param false $muted
     * @param array $muteData
     */
    public function __construct(Player $player, LoginPlayerData $loginData, $muted = false, $muteData = [])
    {
        $this->player = $player;
        $this->loginData = $loginData;
        $this->muted = $muted;
        $this->muteData = $muteData;
        $this->lastHit = time();
        $this->skin = $player->getSkin();
        $this->onlineTime = TextFormat::RED."???";
        $this->time = new DateTime('now');
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return LoginPlayerData
     */
    public function getLoginData(): LoginPlayerData
    {
        return $this->loginData;
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string|null $language
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
    public function setRank(mixed $rank): void
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
     * @return Skin
     */
    public function getBackupSkin(): Skin
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
    public function getClan(): ?Clan
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
     * @return DateTime
     */
    public function getTime(): DateTime
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
        return ($this->getNick() ?? $this->getPlayer()->getName());
    }

    /**
     * @return NetworkLevel|null
     */
    public function getNetworkLevel(): ?NetworkLevel{
        return $this->networkLevel;
    }

    /**
     * @param NetworkLevel|null $networkLevel
     */
    public function setNetworkLevel(?NetworkLevel $networkLevel): void{
        $this->networkLevel = $networkLevel;
    }

    /**
     * @param string|null $status
     */
    public function updateStatus(?string $status): void {
        $clan = $this->getClan();
        $player = $this->getPlayer();

        if($this->isToggleRank()) {
            $nametag = str_replace("{player_name}", $player->getName(), RankProvider::getNameTag("Player")); //PLAYER = DEFAULT
        }else {
            $nametag = str_replace("{player_name}", $player->getName(), RankProvider::getNameTag($this->getRank()));
        }
        $nametag = str_replace("&", TextFormat::ESCAPE, $nametag);
        if($clan !== null) {
            $player->setNameTag(TextFormat::YELLOW."~"." ".$nametag."\n".TextFormat::YELLOW.$clan->getClanTag().($status !== null ? "✎ ".$status : ""));
        }else {
            $player->setNameTag(TextFormat::YELLOW."~"." ".$nametag."\n".TextFormat::YELLOW.($status !== null ? "✎ ".$status : ""));
        }
        $player->setDisplayName($nametag);
    }
}