<?php

namespace ryzerbe\core\util\cache;

class Cache {
    /** @var array */
    private array $cache = [];

    /**
     * @param string|int $key
     * @param mixed $value
     */
    public function set(string|int $key, mixed $value): void{
        $this->cache[$key] = $value;
    }

    /**
     * @param string|int $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string|int $key, mixed $default = null): mixed{
        return $this->cache[$key] ?? $default;
    }

    /**
     * @param string|int $key
     * @return bool
     */
    public function exists(string|int $key): bool{
        return isset($this->cache[$key]);
    }

    /**
     * @param string|int $key
     */
    public function remove(string|int $key): void{
        unset($this->cache[$key]);
    }

    /**
     * @return array
     */
    public function getAll(): array{
        return $this->cache;
    }

    /**
     * @param array $array
     */
    public function setAll(array $array): void{
        $this->cache = $array;
    }
}