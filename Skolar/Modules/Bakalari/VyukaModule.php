<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class VyukaModule extends \Skolar\Modules\BaseModule {

    public function defineParameters($context = null) {
        parent::defineParameters($context);
        
        $this->parameters->url = BakalariToolkit::assignUrl("Přehled výuky", $context["navigace"]);

        if(!empty($this->getRequestParam("view")) || !empty($this->getRequestParam("page"))) {
            $params = array("optional" => array(), "required" => array());

            if(!empty($this->getRequestParam("view"))) {
                $params["optional"] = array('ctl00$cphmain$droppredmety' => $this->getRequestParam("view"));
            }

            if(!empty($this->getRequestParam("page"))) {
                $params["required"] = array(
                    '__CALLBACKID' => 'ctl00$cphmain$roundvyuka$repetk',
                    '__CALLBACKPARAM' => 'c0:KV|2;[];GB|20;12|PAGERONCLICK3|PN' . $this->getRequestParam("page") . ';'
                ); 
            }

            $this->parameters->formparams = BakalariToolkit::getFormParams($context, $params);
        } else {
            $this->parameters->formparams = array();
        }
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \Skolar\Response
     */
    public function parse($content = null) {
        $dom = $content->getDom();
        
        $data = $dom->filterXPath("//table[@class='dxgvTable']//tr[@class='dxgvDataRow']");
        $vyuka = array("vyuka" => array());


        foreach ($data as $n => $row) {
            $cells = (new Crawler($row))->filterXPath("./*/td");

            $lesson = array_filter(array_combine(array("date", "lesson", "topic", "detail", "number"), $cells->extract("_text")), function($item) {
                $item = trim($item);
                return !empty($item);
            });
            $lesson['lesson'] = str_replace(". hod", "", $lesson['lesson']);

            $vyuka['vyuka'][] = $lesson;
        }

        //get pages
        if (count($pages = $dom->filterXPath('//*[@class="dxgvPagerBottomPanel"]//*[contains(@class, "dxp-num")]')) > 0) {
            $vyuka['pages'] = str_replace(array("[", "]"), "", $pages->extract("_text"));
        }

        //get lessons
        if (count($lessons = $dom->filterXPath('//select[@name="ctl00$cphmain$droppredmety"]/option')) > 0) {
            $vyuka['views'] = $lessons->extract(array("_text", "value"));

            array_walk($vyuka["views"], function(&$item) {
                $item = array_combine(["label", "value"], $item);
            });
        }

        return $this->response->setResult($vyuka);
    }

}

?>