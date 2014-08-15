<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class AkceModule extends \Skolar\Modules\BaseModule {

    /**
     * 
     * @param mixed[] $request
     * @return \Skolar\Parameters
     */
    public function defineParameters($context = null) {
        parent::defineParameters($context);
        
        $this->parameters->url = BakalariToolkit::assignUrl("Plán akcí", $context["navigace"]);

        if(!empty($this->getRequestParam("view"))) {
            $this->parameters->formparams = BakalariToolkit::getFormParams($context, array('ctl00$cphmain$dropplan' => $this->getRequestParam("view")));
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

        $types = array(
            "pro učitele:" => 'teacher',
            "pro třídy:" => 'class',
            "čas:" => 'time',
            'místo:' => 'place',
            "popis:" => 'detail'
        );

        $events = array("akce" => array());

        $data = $dom->filterXPath("//*[@class='dxrp dxrpcontent']//*[@class='planinfo']"); //wow, tohle bylo sakra lehké

        $year = explode("/", substr($dom->filterXPath("//*[@class='pololetinadpis']")->text(), -7));
        $year[1] = "20" . $year[1];

        foreach ($data as $item) {
            $event = array();
            $item = new Crawler($item);

            $event['name'] = trim($item->filterXPath("//*[@class='pinadpis']")->text());

            $divs = $item->filterXPath("./*/div[not(@class) and normalize-space()]");

            foreach ($divs as $div) {
                $div = (new Crawler($div))->children();
                $type = trim($div->eq(0)->text());

                if (array_key_exists($type, $types)) {
                    $type = $types[$type];
                    $value = trim($div->eq(1)->text());

                    if ($type == "time") {
                        list($date, $time) = array_pad(array_slice(explode(" ", $value, 3), 1), 2, null); //rozkládáme na array, dále vyberem bez dnu a pokud chybí, dodáme null
                        $value = array();

                        $date = explode(".", substr($date, 0, strlen($date) - 1)); //extrakce data z formátu dd.mm
                        $date[] = ($date[1] >= 9) ? $year[0] : $year[1]; //zjišťujeme rok
                        $value['date'] = strtotime(implode(".", $date)); //skládáme zpět a konvertujeme na unix

                        if (!empty($time)) {
                            $value['time'] = explode(" - ", trim($time, "() \t\n\r\0\x0B"));
                        }
                    } else if ($type == "class" || $type == "teacher") {
                        $value = explode(", ", $value);
                    }

                    $event[$type] = $value;
                }
            }

            $events["akce"][] = $event;
        }
        
        $events["views"] = array_slice(
                $dom->filterXPath('//select[@name="ctl00$cphmain$dropplan"]/option')
                ->extract(["_text", "value"]), 0, 5);
        
        array_walk($events["views"], function(&$item) {
            $item = array_combine(["label", "value"], $item);
        });
        
        return $this->response->setResult($events);
    }

}

?>