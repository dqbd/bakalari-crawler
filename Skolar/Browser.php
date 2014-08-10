<?php
namespace Skolar;

class Browser {

	protected $cache = null;

	public function __construct($cookiepath) {
		$this->cache = new \Skolar\CacheStorage($cookiepath);
	}

	public function load($url) {

	}
}

?>