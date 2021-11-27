<?php

namespace ryzerbe\core\util;

use pocketmine\utils\Config;
use function array_unshift;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;
use const JSON_PRETTY_PRINT;

class UpdatelogEntry {

    private string $title;
    private string $version;
    private string $image;
    private int $timestamp;
    /** @var array  */
    private array $changes;

    public function __construct(string $title, string $version, string $image, int $timestamp){
        $this->title = $title;
        $this->version = $version;
        $this->image = $image;
        $this->timestamp = $timestamp;
        $this->changes = [];
    }

    /**
     * @return string
     */
    public function getTitle(): string{
        return $this->title;
    }

    /**
     * @return string
     */
    public function getImage(): string{
        return $this->image;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int{
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getVersion(): string{
        return $this->version;
    }

    /**
     * @return array
     */
    public function getChanges(): array{
        return $this->changes;
    }

    /**
     * @param string $message
     */
    public function addChange(string $message){
        $this->changes[] = $message;
    }

    public function save(){
        $updates = json_decode(file_get_contents("/var/www/html/updatelog/updates.json"), true);

        array_unshift($updates, [
            "version" => $this->getVersion(),
            "name" => $this->getTitle(),
            "image" => $this->getImage(),
            "changes" => $this->getChanges(),
            "timestamp" => $this->getTimestamp()
        ]);

        file_put_contents("/var/www/html/updatelog/updates.json", json_encode($updates, JSON_PRETTY_PRINT));
    }
}