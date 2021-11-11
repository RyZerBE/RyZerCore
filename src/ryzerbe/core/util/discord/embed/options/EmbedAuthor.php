<?php

namespace ryzerbe\core\util\discord\embed\options;

class EmbedAuthor {
    private string $name;
    private string|null $url;
    private string|null $iconUrl;

    public function __construct(string $name, string|null $url = null, string|null $iconUrl = null){
        $this->name = $name;
        $this->url = $url;
        $this->iconUrl = $iconUrl;
    }

    public function getName(): string{
        return $this->name;
    }

    public function getIconUrl(): ?string{
        return $this->iconUrl;
    }

    public function getUrl(): ?string{
        return $this->url;
    }
}