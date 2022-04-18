<?php

namespace ryzerbe\core\player\data;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerDisconnectPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\TaskUtils;

use function trim;

class LoginPlayerData {
    public const ANDROID = 1;
    public const IOS = 2;
    public const OSX = 3;
    public const FIREOS = 4;
    public const VRGEAR = 5;
    public const VRHOLOLENS = 6;
    public const WINDOWS_10 = 7;
    public const WINDOWS_32 = 8;
    public const DEDICATED = 9;
    public const TVOS = 10;
    public const PS4 = 11;
    public const SWITCH = 12;
    public const XBOX = 13;
    public const LINUX = 20;
    public const KEYBOARD = 1;
    public const TOUCH = 2;
    public const CONTROLLER = 3;
    public const MOTION_CONTROLLER = 4;

    private string $playerName;
    private string $cape_data;
    private int $client_random_id;
    private int $current_input_mode;
    private int $default_input_mode;
    private string $device_id;
    private string $device_model;
    private int $device_os;
    private string $game_version;
    private int $gui_scale;
    private string $language_code;
    private bool $premium_skin;
    private string $self_signed_id;
    private string $server_address;
    private string $skin_data;
    private string $skin_geometry;
    private string $skin_geometry_name;
    private string $skin_id;
    private int $ui_profile;
    private string $address;
    private string $minecraft_id;

    /** @var string[] */
    public static array $deviceOSValues = [
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
        self::LINUX => 'Linux',
    ];

    /** @var string[] */
    public static array $inputValues = [
        self::KEYBOARD => 'Keyboard',
        self::TOUCH => 'Touch',
        self::CONTROLLER => 'Controller',
        self::MOTION_CONTROLLER => 'Motion-Controller',
    ];

    public function __construct(LoginPacket $loginPacket){
        $data = $loginPacket->clientData;
        $this->playerName = $loginPacket->username;
        $this->cape_data = $data["CapeData"] ?? "";
        $this->client_random_id = $data["ClientRandomId"];
        $this->current_input_mode = $data["CurrentInputMode"];
        $this->default_input_mode = $data["DefaultInputMode"];
        $this->device_id = $data["DeviceId"];
        $this->device_model = $data["DeviceModel"];
        $this->device_os = $data["DeviceOS"];
        $this->game_version = $data["GameVersion"];
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
        if(!isset($data["PlayFabId"])) {
			$pk = new PlayerDisconnectPacket();
			$pk->addData("playerName", $this->playerName);
			$pk->addData("message", "&cYou have to join with a valid minecraft client.\n&cYou can't play on our network without PlayFabId (Minecraft-ID)");
			CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
		}
        $this->minecraft_id = $data["PlayFabId"] ?? "NO-PLAY_FAB_ID-FOUND";
        if(trim($this->getDeviceModel()) == ''){
            switch($this->getDeviceOs()){
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

    public function getDeviceModel(): string{
        return $this->device_model;
    }

    public function getDeviceOs(): int{
        return $this->device_os;
    }

    public function getCapeData(): string{
        return $this->cape_data;
    }

    public function getClientRandomId(): int{
        return $this->client_random_id;
    }

    public function getDefaultInputMode(): int{
        return $this->default_input_mode;
    }

    public function getDeviceId(): string{
        return $this->device_id;
    }

    public function getGameVersion(): string{
        return $this->game_version;
    }

    public function getGuiScale(): int{
        return $this->gui_scale;
    }

    public function getLanguageCode(): string{
        return $this->language_code;
    }

    public function getSelfSignedId(): string{
        return $this->self_signed_id;
    }

    public function getServerAddress(): string{
        return $this->server_address;
    }

    public function getSkinData(): string{
        return $this->skin_data;
    }

    public function getSkinGeometry(): string{
        return $this->skin_geometry;
    }

    public function getSkinGeometryName(): string{
        return $this->skin_geometry_name;
    }

    public function getSkinId(): string{
        return $this->skin_id;
    }

    public function getUiProfile(): int{
        return $this->ui_profile;
    }

    public function isPremiumSkin(): mixed{
        return $this->premium_skin;
    }

    public function getAddress(): string{
        return $this->address;
    }

    public function getIP(): string{
        return $this->address;
    }

    public function getDeviceOsName(): string{
        return self::$deviceOSValues[$this->getDeviceOs()] ?? "???";
    }

    public function getOsInputName(): string{
        return self::$inputValues[$this->getCurrentInputMode()] ?? "???";
    }

    public function getCurrentInputMode(): int{
        return $this->current_input_mode;
    }

    /**
     * @return string
     */
    public function getMinecraftId(): string{
        return $this->minecraft_id;
    }
}