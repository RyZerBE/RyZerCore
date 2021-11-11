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

    private string $webhook;

    private array $data = [];

    private string $method = "";

    public function __construct(string $webhook){
        $this->webhook = $webhook;
    }

    public function setWebhook(string $webhook): void{
        $this->webhook = $webhook;
    }

    public function getUsername(): string{
        return $this->data["username"] ?? "";
    }

    public function setUsername(string $username): void{
        $this->data["username"] = $username;
    }

    public function setMessage(string $message): void{
        $this->data["content"] = $message;
    }

    public function getMessage(): string{
        return $this->data["content"];
    }

    public function addEmbed(DiscordEmbed $embed): void{
        $this->data["embeds"][] = $embed->getData();
    }

    public function setTextToSpeech(bool $tts): void{
        $this->data["tts"] = $tts;
    }

    public function setMethod(string $method): void{
        $this->method = $method;
    }

    public function send(bool $async = true): bool|string{
        if($async){
            Server::getInstance()->getAsyncPool()->submitTask(new AsyncSendScheduler($this));
            return true;
        }
        return self::sendMessage($this);
    }

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

    public function getWebhook(): string{
        return $this->webhook;
    }

    public function getData(): array{
        return $this->data;
    }

    public function getMethod(): string{
        return $this->method;
    }
}