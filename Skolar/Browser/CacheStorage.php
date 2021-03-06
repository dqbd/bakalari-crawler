<?php

namespace Skolar\Browser;

class CacheStorage extends \GuzzleHttp\Cookie\FileCookieJar {
    protected $filename;

    protected $permanent = false;

    private $toDestroy = false;

    public function save($filename) {
        if($this->toDestroy == false && $this->filename) {
            $json = array("cookies" => array(), "permanent" => $this->isPermanentLogin());

            foreach ($this as $cookie) {
                if ($cookie->getExpires() && !$cookie->getDiscard()) {
                    $json["cookies"][] = $cookie->toArray();
                }
            }

            if (false === file_put_contents($filename, json_encode($json))) {
                throw new \RuntimeException("Unable to save file {$filename}");
            }
        }
    }

    public function load($filename) {
        $json = file_get_contents($filename);

        if($json === false) {
            throw new \RuntimeException("Unable to load file {$filename}");
        }

        $data = \GuzzleHttp\json_decode($json, true);

        if (is_array($data) && is_array($data["cookies"])) {
            foreach ($data["cookies"] as $cookie) {
                $this->setCookie(new \GuzzleHttp\Cookie\SetCookie($cookie));
            }

            $this->setPermanentLogin($data["permanent"]);

        } else if(strlen($data) || strlen($data["cookies"])) {
            throw new \RuntimeException("Invalid cache file: {$filename}");
        }
    }

    public function setPermanentLogin($permanent) {
        $this->permanent = $permanent;
    }

    public function isPermanentLogin() {
        return $this->permanent;
    }

    public function removeCache() {
        $this->toDestroy = true;
        unset($this->filename);
    }
}

?>