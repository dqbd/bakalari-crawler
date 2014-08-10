<?php

namespace Skolar\Modules;

abstract class BaseModule {
    /**
    * Odpověď každého modulu
    *
    * @var \Skolar\Response Response
    */
    protected $response = null;
    
    /**
    * Parametry pro jednotlivý modul
    *
    * @var \Skolar\Parameters Parameters
    */
    public $parameters = null;
    public $request = null;

    public function __construct($request) {
        $this->response = new \Skolar\Response();
        $this->parameters = new \Skolar\Parameters();

        $this->request = $request;

        $this->defineParameters($request);
    }

    // abstract public function getParameters($request = null);
    abstract public function parse($content = null);

    public function defineParameters($context = null) {
        $name = explode("\\", strtolower(str_replace("Module", "", get_class($this))));
        $this->parameters->name = end($name);
    }

    public function preParse($content) {
        return null;
    }

    public function postParse($content) {
        return null;
    }

    public function getRequirements() {
        $defined = $this->defineParameters();

        if($defined !== null) {
            return (is_array($defined)) ? $defined : array($defined);
        }
    }


    public function getResponse() {
        return $this->response;
    }

    public function getRequest() {
        return $this->request;
    }

    public function getRequestParam($name) {
        return $this->request->get($name);
    }
    
    public function getParameters() {
        return $this->parameters;
    }

    public function getName() {
        return $this->parameters->name;
    }

    public function getUrl() {
        return $this->parameters->url ?: false;
    }

    public function __toString() {
        return $this->getName();
    }
}

?>
