<?php

namespace BakaParser\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class NavigaceModule extends \BakaParser\Modules\BaseModule {

    /**
     * 
     * @param mixed[] $request
     * @return \BakaParser\Parameters
     */
    public function getParameters($request = null) {
        $this->parameters->url = "uvod.aspx";
        return $this->parameters;
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \BakaParser\Response
     */
    public function parse($request) {
        $odkazy = $request->filterXPath("//*[contains(@id, 'hlavnimenu')]//a");
        $odkazy = array_combine($odkazy->extract(array("href")), $odkazy->extract(array("_text")));
        
        $this->response->setResult(array("navigace" => $odkazy));
        
        return $this->response;
    }

}

?>
