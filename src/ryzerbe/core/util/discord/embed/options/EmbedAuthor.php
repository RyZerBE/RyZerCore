<?php

namespace ryzerbe\core\util\discord\embed\options;

class EmbedAuthor {
    /** @var string */
    private string $name;
    /** @var string|null */
    private string|null $url;
    /** @var string|null */
    private string|null $iconUrl;

    /**
     * EmbedAuthor constructor.
     *
     * @param string $name
     * @param string|null $url
     * @param string|null $iconUrl
     */
    public function __construct(string $name, string|null $url = null, string|null $iconUrl = null){
        $this->name = $name;
        $this->url = $url;
        $this->iconUrl = $iconUrl;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getIconUrl(): ?string{
        return $this->iconUrl;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string{
        return $this->url;
    }
}