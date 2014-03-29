<?php

namespace BakaParser\Handlers;

class ServerHandler implements \BakaParser\Handlers\BaseHandler {

    /** @var \BakaParser\Modules\BaseModule */
    private $module = null;
    
    /** @var mixed[] */
    private $params = null;

    /**
     * @param \BakaParser\Modules\BaseModule $module
     * @param mixed[] $parameters
     */
    public function __construct(\BakaParser\Modules\BaseModule $module, $parameters) {
        
        $this->module = $module;
        $this->params = $parameters;
    }

    /**
     * Vyjede výsledek
     * 
     * @return \BakaParser\Handlers\Response
     */
    public function output() {
        return $this->module->parse($this->params);
    }

}

?>