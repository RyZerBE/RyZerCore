<?php

namespace ryzerbe\core\util\discord\embed;

use DateTime;
use pocketmine\utils\Color;
use ryzerbe\core\util\discord\embed\options\EmbedAuthor;
use ryzerbe\core\util\discord\embed\options\EmbedField;

class DiscordEmbed {
    private array $data = [];

    public function setAuthor(EmbedAuthor $author): void{
        $this->data["author"]["name"] = $author->getName();
        if(!is_null($author->getUrl())) $this->data["author"]["url"] = $author->getUrl();
        if(!is_null($author->getIconUrl())) $this->data["author"]["icon_url"] = $author->getIconUrl();
    }

    public function setTitle(string $title): void{
        $this->data["title"] = $title;
    }

    public function setDescription(string $description): void{
        $this->data["description"] = $description;
    }

    public function setColor(Color|int $color): void{
        if($color instanceof Color) $color = $color->toARGB();
        $this->data["color"] = $color;
    }

    public function addField(EmbedField $field): void{
        $this->data["fields"][] = [
            "name" => $field->getName(), "value" => $field->getValue(), "inline" => $field->isInline(),
        ];
    }

    public function setThumbnail(string $url): void{
        $this->data["thumbnail"] ["url"] = $url;
    }

    public function setImage(string $url): void{
        $this->data["image"]["url"] = $url;
    }

    public function setFooter(string $text, string $iconUrl = null): void{
        $this->data["footer"]["text"] = $text;
        if(!is_null($iconUrl)) $this->data["footer"]["icon_url"] = $iconUrl;
    }

    public function setDateTime(DateTime $dateTime): void{
        $this->data["timestamp"] = $dateTime->format("Y-m-d\TH:i:s.v\Z");
    }

    public function getData(): array{
        return $this->data;
    }
}