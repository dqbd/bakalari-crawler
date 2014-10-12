<?php

//init old PHP fallbacks
require_once __DIR__ . "/userland.php";

$config = array();

define("SKOLAR_BASE_DIR", dirname(__DIR__));
define("SKOLAR_CACHE_DIR", SKOLAR_BASE_DIR . DIRECTORY_SEPARATOR . "cache");
define("SKOLAR_TESTFILES_DIR", SKOLAR_BASE_DIR . DIRECTORY_SEPARATOR . "testfiles");

define("SKOLAR_LOCAL", (getenv('SERVER_CONTEXT') != "prod"));

$config["database"] = array(
    "driver" => "mysqli",
    "user" => "username",
    "pass" => "password",
    "database" => "database"
); 

$config["browser_headers"] = array(
    "Connection" => "keep-alive",
    "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
    "Accept-Language" => "cs-CZ,cs;q=0.8,en;q=0.6,en-US;q=0.4",
    "User-Agent" => "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.68 Safari/537.36"
);  

$config["timelimit"] = 90;

$module_config = json_decode(file_get_contents(__DIR__ . "/routes.json"), true);
$config = array_merge($config, $module_config);
?>
