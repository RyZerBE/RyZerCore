<?php

namespace ryzerbe\core\player\data;

use pocketmine\network\mcpe\protocol\LoginPacket;
use function trim;

class LoginPlayerData {

    /** @var string */
    private string $playerName;
    /** @var string */
    private string $cape_data;
    /** @var int */
    private int $client_random_id;
    /** @var int */
    private int $current_input_mode;
    /** @var int */
    private int $default_input_mode;
    /** @var string */
    private string $device_id;
    /** @var string */
    private string $device_model;
    /** @var int */
    private int $device_os;
    /** @var string */
    private string $game_version;
    /** @var int */
    private int $gui_scale;
    /** @var string */
    private string $language_code;
    /** @var bool */
    private bool $premium_skin;
    /** @var string */
    private string $self_signed_id;
    /** @var string */
    private string $server_address;
    /** @var string */
    private string $skin_data;
    /** @var string */
    private string $skin_geometry;
    /** @var string */
    private string $skin_geometry_name;
    /** @var string */
    private string $skin_id;
    /** @var int */
    private int $ui_profile;
    /** @var string */
    private string $address;

    /** @var int */
    public const ANDROID = 1;
    /** @var int */
    public const IOS = 2;
    /** @var int */
    public const OSX = 3;
    /** @var int */
    public const FIREOS = 4;
    /** @var int */
    public const VRGEAR = 5;
    /** @var int */
    public const VRHOLOLENS = 6;
    /** @var int */
    public const WINDOWS_10 = 7;
    /** @var int */
    public const WINDOWS_32 = 8;
    /** @var int */
    public const DEDICATED = 9;
    /** @var int */
    public const TVOS = 10;
    /** @var int */
    public const PS4 = 11;
    /** @var int */
    public const SWITCH = 12;
    /** @var int */
    public const XBOX = 13;
    /** @var int */
    public const LINUX = 20; // For linux

    /** @var int */
    public const KEYBOARD = 1;
    /** @var int */
    public const TOUCH = 2;
    /** @var int */
    public const CONTROLLER = 3;
    /** @var int */
    public const MOTION_CONTROLLER = 4;

    /** @var string[] */
    private array $deviceOSValues = [
        self::ANDROID => 'Android',
        self::IOS => 'iOS',
        self::OSX => 'OSX',
        self::FIREOS => 'FireOS',
        self::VRGEAR => 'VRGear',
        self::VRHOLOLENS => 'VRHololens',
        self::WINDOWS_10 => 'Win10',
        self::WINDOWS_32 => 'Win32',
        self::DEDICATED => 'Dedicated',
        self::TVOS => 'TVOS',
        self::PS4 => 'PS4',
        self::SWITCH => 'Nintendo Switch',
        self::XBOX => 'Xbox',
        self::LINUX => 'Linux'
    ];

    /** @var string[] */
    private array $inputValues = [
        self::KEYBOARD => 'Keyboard',
        self::TOUCH => 'Touch',
        self::CONTROLLER => 'Controller',
        self::MOTION_CONTROLLER => 'Motion-Controller'
    ];

    /**
     * LoginPlayerData constructor.
     *
     * @param LoginPacket $loginPacket
     */
    public function __construct(LoginPacket $loginPacket)
    {
        $data = $loginPacket->clientData;
        $this->playerName = $loginPacket->username;
        $this->cape_data = $data["CapeData"] ?? "";
        $this->client_random_id = $data["ClientRandomId"];
        $this->current_input_mode = $data["CurrentInputMode"];
        $this->default_input_mode = $data["DefaultInputMode"];
        $this->device_id = $data["DeviceId"];
        $this->device_model = $data["DeviceModel"];
        $this->device_os = $data["DeviceOS"];
        $this->game_version= $data["GameVersion"];
        $this->gui_scale = $data["GuiScale"];
        $this->language_code = $data["LanguageCode"];
        $this->premium_skin = $data["PremiumSkin"] ?? false;
        $this->self_signed_id = $data["SelfSignedId"];
        $this->server_address = $data["ServerAddress"] ?? "5.181.151.61";
        $this->skin_data = $data["SkinData"];
        $this->skin_geometry = $data["SkinGeometryData"] ?? "";
        $this->skin_geometry_name = "";
        $this->skin_id = $data["SkinId"] ?? 1;
        $this->ui_profile = $data["UIProfile"] ?? 1;
        $this->address = $data['Waterdog_IP'] ?? "0.0.0.0";

        if (trim($this->getDeviceModel()) == '') {
            switch ($this->getDeviceOs()) {
                case self::ANDROID:
                    $this->device_os = self::LINUX;
                    $this->device_model = "Linux";
                    break;
                case self::XBOX:
                    $this->device_os = self::XBOX;
                    $this->device_model = "Xbox One";
                    break;
            }
        }
    }

    /**
     * @return string
     */
    public function getCapeData(): string
    {
        return $this->cape_data;
    }

    /**
     * @return int
     */
    public function getClientRandomId(): int
    {
        return $this->client_random_id;
    }

    /**
     * @return int
     */
    public function getCurrentInputMode(): int
    {
        return $this->current_input_mode;
    }

    /**
     * @return int
     */
    public function getDefaultInputMode(): int
    {
        return $this->default_input_mode;
    }

    /**
     * @return string
     */
    public function getDeviceId(): string
    {
        return $this->device_id;
    }

    /**
     * @return string
     */
    public function getDeviceModel(): string
    {
        return $this->device_model;
    }

    /**
     * @return int
     */
    public function getDeviceOs(): int
    {
        return $this->device_os;
    }

    /**
     * @return string
     */
    public function getGameVersion(): string
    {
        return $this->game_version;
    }

    /**
     * @return int
     */
    public function getGuiScale(): int
    {
        return $this->gui_scale;
    }

    /**
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->language_code;
    }

    /**
     * @return string
     */
    public function getSelfSignedId(): string
    {
        return $this->self_signed_id;
    }

    /**
     * @return string
     */
    public function getServerAddress(): string
    {
        return $this->server_address;
    }

    /**
     * @return string
     */
    public function getSkinData(): string
    {
        return $this->skin_data;
    }

    /**
     * @return string
     */
    public function getSkinGeometry(): string
    {
        return $this->skin_geometry;
    }

    /**
     * @return string
     */
    public function getSkinGeometryName(): string
    {
        return $this->skin_geometry_name;
    }

    /**
     * @return string
     */
    public function getSkinId(): string
    {
        return $this->skin_id;
    }

    /**
     * @return int
     */
    public function getUiProfile(): int
    {
        return $this->ui_profile;
    }

    /**
     * @return bool
     */
    public function isPremiumSkin(): mixed{
        return $this->premium_skin;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getIP(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getDeviceOsName(): string{
        return $this->deviceOSValues[$this->getDeviceOs()] ?? "???";
    }

    /**
     * @return string
     */
    public function getOsInputName(): string{
        return $this->inputValues[$this->getCurrentInputMode()] ?? "???";
    }
}