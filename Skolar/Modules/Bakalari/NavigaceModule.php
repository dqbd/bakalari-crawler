<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class NavigaceModule extends \Skolar\Modules\BaseModule {

    public function defineParameters($context = null) {
        parent::defineParameters($context);

        $this->parameters->url = "uvod.aspx";
    }

    public function preParse($content = null) {
        return (count($this->postParse($content)) > 0);
    }

    public function parse($content = null) {
        $links = $this->scrapNavigaction($content);

        $result = array();

        //load all modules to get their name
        foreach(\Skolar\Configuration::get("handlers")["bakalari"]["modules"] as $module_info) {
            $module = \Skolar\Dispatcher::createModule($module_info["name"], "bakalari");
            $module->defineParameters(array("navigace" => $links));

            if(($url = $module->getUrl())) {
                $result[] = $module->getName();
            }
        }
        
        return $this->getResponse()->setResult(array("navigace" => $result));
        
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
