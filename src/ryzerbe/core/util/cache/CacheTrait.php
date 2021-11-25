<?php

namespace ryzerbe\core\util\cache;

trait CacheTrait {
    /** @var ?Cache */
    private ?Cache $cache = null;

    public function resetCache(): void{
        $this->checkCache();
        $this->cache->setAll([]);
    }

    private function checkCache(): void{
        if($this->cache !== null) return;
        $this->cache = new Cache();
    }

    /**
     * @return Cache
     */
    public function getCache(): Cache{
        $this->checkCache();
        return $this->cache;
    }
}