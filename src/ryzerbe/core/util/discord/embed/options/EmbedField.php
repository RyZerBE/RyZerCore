<?php

namespace ryzerbe\core\util\discord\embed\options;

class EmbedField {
    /** @var string */
    private string $name;
    /** @var string */
    private string $value;
    /** @var bool */
    private bool $inline;

    /**
     * EmbedField constructor.
     *
     * @param string $name
     * @param string $value
     * @param bool $inline
     */
    public function __construct(string $name, string $value, bool $inline = false){
        $this->name = $name;
        $this->value = $value;
        $this->inline = $inline;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue(): string{
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isInline(): bool{
        return $this->inline;
    }
}