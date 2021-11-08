<?php

namespace ryzerbe\core\util\embed;

class DiscordEmbed {
    /** @var array */
    private array $data = [];

    /**
     * @param EmbedAuthor $author
     */
    public function setAuthor(EmbedAuthor $author): void{
        $this->data["author"]["name"] = $author->getName();
        if(!is_null($author->getUrl())) $this->data["author"]["url"] = $author->getUrl();
        if(!is_null($author->getIconUrl())) $this->data["author"]["icon_url"] = $author->getIconUrl();
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void{
        $this->data["title"] = $title;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void{
        $this->data["description"] = $description;
    }

    /**
     * @param Color|int $color
     */
    public function setColor(Color|int $color): void{
        if($color instanceof Color) $color = $color->toARGB();
        $this->data["color"] = $color;
    }

    /**
     * @param EmbedField $field
     */
    public function addField(EmbedField $field): void{
        $this->data["fields"][] = [
            "name" => $field->getName(), "value" => $field->getValue(), "inline" => $field->isInline(),
        ];
    }

    /**
     * @param string $url
     */
    public function setThumbnail(string $url): void{
        $this->data["thumbnail"] ["url"] = $url;
    }

    /**
     * @param string $url
     */
    public function setImage(string $url): void{
        $this->data["image"]["url"] = $url;
    }

    /**
     * @param string $text
     * @param string|null $iconUrl
     */
    public function setFooter(string $text, string $iconUrl = null): void{
        $this->data["footer"]["text"] = $text;
        if(!is_null($iconUrl)) $this->data["footer"]["icon_url"] = $iconUrl;
    }

    /**
     * @param DateTime $dateTime
     */
    public function setDateTime(DateTime $dateTime): void{
        $this->data["timestamp"] = $dateTime->format("Y-m-d\TH:i:s.v\Z");
    }

    /**
     * @return array
     */
    public function getData(): array{
        return $this->data;
    }
}