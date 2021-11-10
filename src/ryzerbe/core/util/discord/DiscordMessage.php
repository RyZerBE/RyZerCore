<?php

namespace ryzerbe\core\util\discord;

use pocketmine\Server;
use ryzerbe\core\util\discord\scheduler\AsyncSendScheduler;
use ryzerbe\core\util\discord\embed\DiscordEmbed;

class DiscordMessage {

    public const REQUEST_POST = "POST";
    public const REQUEST_GET = "GET";
    public const REQUEST_PUT = "PUT";
    public const REQUEST_DELETE = "DELETE";
    public const REQUEST_PATCH = "PATCH";
    /** @var string */
    private string $webhook;
    /** @var array */
    private array $data = [];
    /** @var string */
    private string $method = "";

    /**
     * DiscordMessage constructor.
     *
     * @param string $webhook
     */
    public function __construct(string $webhook){
        $this->webhook = $webhook;
    }

    /**
     * @param string $webhook
     */
    public function setWebhook(string $webhook): void{
        $this->webhook = $webhook;
    }

    /**
     * @return string
     */
    public function getUsername(): string{
        return $this->data["username"] ?? "";
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void{
        $this->data["username"] = $username;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void{
        $this->data["content"] = $message;
    }

    /**
     * @return string
     */
    public function getMessage(): string{
        return $this->data["content"];
    }

    /**
     * @param DiscordEmbed $embed
     */
    public function addEmbed(DiscordEmbed $embed): void{
        $this->data["embeds"][] = $embed->getData();
    }

    /**
     * @param bool $tts
     */
    public function setTextToSpeech(bool $tts): void{
        $this->data["tts"] = $tts;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void{
        $this->method = $method;
    }

    /**
     * @param bool $async
     * @return bool|string
     */
    public function send(bool $async = true): bool|string{
        if($async){
            Server::getInstance()->getAsyncPool()->submitTask(new AsyncSendScheduler($this));
            return true;
        }
        return self::sendMessage($this);
    }

    /**
     * @param DiscordMessage $message
     * @return bool|string
     */
    public static function sendMessage(DiscordMessage $message): bool|string{
        $ch = curl_init($message->getWebhook());
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message->getData()));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if(!empty($message->getMethod())) curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $message->getMethod());
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * @return string
     */
    public function getWebhook(): string{
        return $this->webhook;
    }

    /**
     * @return array
     */
    public function getData(): array{
        return $this->data;
    }

    /**
     * @return string
     */
    public function getMethod(): string{
        return $this->method;
    }
}