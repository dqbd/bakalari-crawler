<?php
namespace Skolar;

class Configuration {

	public static $config;

	public static function get($key) {
		return self::$config[$key];
	}

	public static function setConfig($config) {
		self::$config = $config;
	}
}

?>