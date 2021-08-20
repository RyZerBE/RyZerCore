<?php


namespace baubolp\core\player;


use pocketmine\network\mcpe\protocol\LoginPacket;

class LoginPlayerData
{
    /** @var string */
    private string $playerName;
    /** @var string */
    private $cape_data;
    /** @var int */
    private $client_random_id;
    /** @var int */
    private $current_input_mode;
    /** @var int */
    private $default_input_mode;
    /** @var string */
    private $device_id;
    /** @var string */
    private $device_model;
    /** @var int */
    private $device_os;
    /** @var string */
    private $game_version;
    /** @var int */
    private $gui_scale;
    /** @var string */
    private $language_code;
    /** @var int */
    /** @var bool */
    private $premium_skin;
    /** @var string */
    private $self_signed_id;
    /** @var string */
    private $server_address;
    /** @var string */
    private $skin_data;
    /** @var string */
    private $skin_geometry;
    /** @var string */
    private string $skin_geometry_name;
    /** @var string */
    private $skin_id;
    /** @var int */
    private $ui_profile;
    /** @var string */
    private $address;

    /**
     * LoginPlayerData constructor.
     *
     * @param LoginPacket $loginPacket
     */
    public function __construct(LoginPacket $loginPacket)
    {
        $data = $loginPacket->clientData;
        $this->playerName = $loginPacket->username;
        $this->cape_data = $data["CapeData"];
        $this->client_random_id = $data["ClientRandomId"];
        $this->current_input_mode = $data["CurrentInputMode"];
        $this->default_input_mode = $data["DefaultInputMode"];
        $this->device_id = $data["DeviceId"];
        $this->device_model = $data["DeviceModel"];
        $this->device_os = $data["DeviceOS"];
        $this->game_version= $data["GameVersion"];
        $this->gui_scale = $data["GuiScale"];
        $this->language_code = $data["LanguageCode"];
        $this->premium_skin = $data["PremiumSkin"];
        $this->self_signed_id = $data["SelfSignedId"];
        $this->server_address = $data["ServerAddress"];
        $this->skin_data = $data["SkinData"];
        $this->skin_geometry = $data["SkinGeometryData"];
        $this->skin_geometry_name = "";
        $this->skin_id = $data["SkinId"];
        $this->ui_profile = $data["UIProfile"];
        $this->address = $data['Waterdog_IP'];
       // $this->xuid = $data['Waterdog_XUID']; #WaterdogPE
       // $this->uuid = $data['Waterdog_OriginalUUID']; #WaterdogPE
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

    public function getDataArray()
    {
        return [
          'playerName' => $this->playerName,
          'deviceModel' => $this->getDeviceModel(),
          'deviceId' => $this->getDeviceId(),
          'capeData' => $this->getCapeData(),
          'clientRandomId' => $this->getClientRandomId(),
          'currentInputMode' => $this->getCurrentInputMode(),
          'defaultInputMode' => $this->getDefaultInputMode(),
          'deviceOs' => $this->getDeviceOs(),
          'gameVersion' => $this->getGameVersion(),
          'guiScale' => $this->getGuiScale(),
          'languageCode' => $this->getLanguageCode(),
          'selfSignedId' => $this->getSelfSignedId(),
          'serverAddress' => $this->getServerAddress(),
          'skinData' => $this->getSkinData(),
          'skimGeometry' => $this->getSkinGeometry(),
          'skinId' => $this->getSkinId(),
          'uiProfile' => $this->getUiProfile(),
          'address' =>   $this->getAddress(),
          'ip' => $this->getAddress(),
        ];
    }

    public function toArray()
    {
        return [
            'playerName' => $this->playerName,
            'deviceModel' => $this->getDeviceModel(),
            'deviceId' => $this->getDeviceId(),
            'capeData' => $this->getCapeData(),
            'clientRandomId' => $this->getClientRandomId(),
            'currentInputMode' => $this->getCurrentInputMode(),
            'defaultInputMode' => $this->getDefaultInputMode(),
            'deviceOs' => $this->getDeviceOs(),
            'gameVersion' => $this->getGameVersion(),
            'guiScale' => $this->getGuiScale(),
            'languageCode' => $this->getLanguageCode(),
            'selfSignedId' => $this->getSelfSignedId(),
            'serverAddress' => $this->getServerAddress(),
            'skinData' => $this->getSkinData(),
            'skimGeometry' => $this->getSkinGeometry(),
            'skinId' => $this->getSkinId(),
            'uiProfile' => $this->getUiProfile(),
            'address' =>   $this->getAddress(),
            'ip' => $this->getAddress()
        ];
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
}