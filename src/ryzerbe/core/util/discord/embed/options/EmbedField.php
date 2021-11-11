<?php

namespace ryzerbe\core\util\discord\embed\options;

class EmbedField {
    private string $name;
    private string $value;
    private bool $inline;

    public function __construct(string $name, string $value, bool $inline = false){
        $this->name = $name;
        $this->value = $value;
        $this->inline = $inline;
    }

    public function getName(): string{
        return $this->name;
    }

    public function getValue(): string{
        return $this->value;
    }

    public function isInline(): bool{
        return $this->inline;
    }
}