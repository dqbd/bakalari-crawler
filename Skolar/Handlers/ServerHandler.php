<?php

namespace Skolar\Handlers;

class ServerHandler implements \Skolar\Handlers\BaseHandler {

    /** @var \Skolar\Modules\BaseModule */
    private $module = null;
    
    /** @var mixed[] */
    private $params = null;

    /**
     * @param \Skolar\Modules\BaseModule $module
     * @param mixed[] $parameters
     */
    public function __construct(\Skolar\Modules\BaseModule $module, $parameters) {
        
        $this->module = $module;
        $this->params = $parameters;
    }

    /**
     * Vyjede výsledek
     * 
     * @return \Skolar\Handlers\Response
     */
    public function output() {
        return $this->module->parse();
    }

}

?>