<?php

namespace Skolar;

use \Guzzle\Common\Exception\RuntimeException;

class CacheStorage extends \Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar {
    protected $filename;

    protected $navdom = false;
    protected $permanent = false;

    public function __construct($cacheFile) {
        $this->filename = $cacheFile;
        $this->load();
    }

    public function __destruct() {
        $this->persist();
    }

    protected function persist() {
        $cookies = $this->serialize();

        $file = array("cookies" => $cookies, "navdom" => $navdom, "permament" => $permament);

        if (false === file_put_contents($this->filename, $file)) {
            throw new RuntimeException('Unable to open file ' . $this->filename);
        }
    }

    protected function load() {
        $json = file_get_contents($this->filename);
        if (false === $json) {
            throw new RuntimeException('Unable to open file ' . $this->filename);
        }

        $json = json_decode($json);

        $this->unserialize($json["cookies"]);
        $this->cookies = $this->cookies ?: array();

        $this->navdom = $json["navdom"] ?: false;
        $this->permament = is_bool($json["permament"]) ? $json["permament"] : false;

    }

    public function getNavCache() {
        return $this->navdom;
    }

    public function isPermanentLogin() {
        return $this->permament;
    }



}

?>