<?php

namespace BakaParser\Modules;

abstract class BaseModule {
    /**
    * Odpověď každého modulu
    *
    * @var \BakaParser\Response Response
    */
    protected $response = null;
    
    /**
    * Parametry pro jednotlivý modul
    *
    * @var \BakaParser\Parameters Parameters
    */
    public $parameters = null;


    public function __construct() {
        $this->response = new \BakaParser\Response();
        $this->parameters = new \BakaParser\Parameters();
    }

    abstract public function getParameters($request = null);
    abstract public function parse($request);
}

?>
