<?php

namespace BakaParser;

class Handler {    
    /**
     * Instantizace a spuštění handleru včetně modulu
     * 
     * @param \Klein\Request $request
     * @param string[] $config
     */
    public function output($request, $config) {
        
        //určit modul
        $module_name = "\\BakaParser\\Modules\\";
        $handler_name = "\\BakaParser\\Handlers\\";
        
        foreach($config["modules"] as $type => $list) {
            if(array_search($request->action, $list) !== false) {
                $module_name .= ucfirst($type) . "\\" . ucfirst($request->action) . "Module";
                $handler_name .= ucfirst($type) . "Handler";
                break;
            }
        }
        
        //$handler->setParameters(array_merge($request->paramsPost(), $request->paramsNamed()));
        $handler = new $handler_name((new $module_name()), $request->params());
        
        return $handler->output();
    }
}

?>