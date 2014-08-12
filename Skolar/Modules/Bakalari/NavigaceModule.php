<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class NavigaceModule extends \Skolar\Modules\BaseModule {
    public function defineParameters($context = null) {
        parent::defineParameters();
        $this->parameters->url = "uvod.aspx";

        return "url";
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \Skolar\Response
     */
    public function parse($content = null) {
        $odkazy = $content->filterXPath("//*[contains(@id, 'hlavnimenu')]//a");
        $odkazy = array_combine($odkazy->extract(array("href")), $odkazy->extract(array("_text")));
        
        $this->response->setResult(array("navigace" => $odkazy));
        
        return $this->response;
    }

    public function postParse($content = null) {
        $data = $this->parse($content)->getData();

        if(array_key_exists("navigace", $data)) {
            return $data["navigace"];
        }

        return null;
    }
}

?>
