<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class NavigaceModule extends \Skolar\Modules\BaseModule {

    public function defineParameters($context = null) {
        parent::defineParameters();
        $this->parameters->url = "uvod.aspx";
    }

    public function preParse($content = null) {
        return (count($this->postParse($content)) > 0);
    }

    /**
     * 
     * @param \Skolar\Browser\PageData $content
     * @return \Skolar\Response
     */
    public function parse($content = null) {
        $links = $this->scrapNavigaction($content);

        $result = array();

        //load all modules to get their name
        foreach(\Skolar\Configuration::get("handlers")["bakalari"]["modules"] as $module_info) {
            $module = \Skolar\Dispatcher::createModule($module_info["name"], "bakalari");
            $module->defineParameters(array("navigace" => $links));

            if(!empty($url = $module->getUrl())) {
                $result[] = $module->getName();
            }
        }
        
        $this->response->setResult(array("navigace" => $result));
        
        return $this->response;
    }

    public function postParse($content = null) {
        return $this->scrapNavigaction($content);
    }

    private function scrapNavigaction($content = null) {
        $odkazy = $content->getDom()->filterXPath("//*[contains(@id, 'hlavnimenu')]//a");
        return array_combine($odkazy->extract(array("href")), $odkazy->extract(array("_text")));
    }
}

?>
