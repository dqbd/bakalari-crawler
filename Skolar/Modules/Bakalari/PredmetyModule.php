<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class PredmetyModule extends \Skolar\Modules\BaseModule {

    public function defineParameters($context = null) {
        parent::defineParameters($context);

        $this->parameters->url = BakalariToolkit::assignUrl("Přehled předmětů", $context["navigace"]);
    }

    public function parse($content = null) {
        $rows = $request->getDom()->filterXPath("//*[@class='dxrp dxrpcontent']//tr");

        $lessons = array();
        foreach ($rows as $n => $item) {
            $data = (new Crawler($item))->children();

            if ($n == 0) {
                $lessons['hlavicka'] = $data->extract("_text");
            } else {
                $lesson = array();
                foreach ($data as $x => $field) {
                    if (!empty($field->nodeValue)) {
                        $lesson[$x] = $field->nodeValue;
                    }
                }
                $lessons['predmety'][] = $lesson;
            }
        }

        return $this->getResponse()->setResult($lessons);
    }

}

?>