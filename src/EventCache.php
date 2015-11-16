<?php

namespace Starsquare\Mmf;

class EventCache {
    protected $file;
    protected $cache;

    public function __construct($file) {
        $this->file = $file;
        $this->loadCache();
    }

    protected function loadCache() {
        touch($this->file);
        $this->cache = json_decode(file_get_contents($this->file), true);

        if (null === $this->cache) {
            $this->cache = array();
        }
    }

    public function get($id) {
        return (isset($this->cache[$id]) ? $this->cache[$id] : null);
    }

    public function set($id, array $data) {
        $this->cache[$id] = $data;
    }

    public function save() {
        file_put_contents($this->file, json_encode($this->cache));
    }
}
