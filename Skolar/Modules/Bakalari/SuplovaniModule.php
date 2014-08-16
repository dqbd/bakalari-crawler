<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class SuplovaniModule extends \Skolar\Modules\BaseModule {

    public function defineParameters($context = null) {
        parent::defineParameters($context);
        
        $this->parameters->url = BakalariToolkit::assignUrl("Suplování", $context["navigace"]);

        if($this->getRequestParam("view")) {
           $this->parameters->formparams = BakalariToolkit::getFormParams($context, array('ctl00$cphmain$radiosuplov' => $this->getRequestParam("view")));
        } else {
           $this->parameters->formparams = array();
        }
    }

    public function parse($content = null) {
        $dom = $content->getDom();

        $data = $dom->filterXPath("//*[@class='dxrp dxrpcontent']//div[(@class='supden' or @class='suphod') and text() != 'Žádné změny']");

        $year = explode("/", substr($dom->filterXPath("//*[@class='pololetinadpis']")->text(), -7));
        $year[1] = "20" . $year[1];

        $suplovani = array("suplovani" => array());

        $last = array();

        foreach ($data as $item) {
            $text = $item->nodeValue;

            if ($item->getAttribute("class") == "supden") {
                $text = BakalariToolkit::getDate($text);

                if (empty($last) || $last["date"] != $text) {
                    if(!empty($last)) {
                        $suplovani["suplovani"][] = $last;
                    }

                    $last = array("date" => $text, "changes" => array());
                }
            } else {
                $last["changes"][] = $text;
            }
        }

        if (!empty($last)) {
            $suplovani["suplovani"][] = $last;
        }
        
        $suplovani["views"] = $dom->filterXPath('//select[@name="ctl00$cphmain$radiosuplov"]/option')->extract(["_text", "value"]);

        array_walk($suplovani["views"], function(&$item) {
            $item = array_combine(["label", "value"], $item);
        });
        
        return $this->getResponse()->setResult($suplovani);
    }

}

?>