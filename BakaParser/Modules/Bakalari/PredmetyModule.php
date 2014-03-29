<?php

namespace BakaParser\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class PredmetyModule extends \BakaParser\Modules\BaseModule {

    /**
     * 
     * @param mixed[] $request
     * @return \BakaParser\Parameters
     */
    public function getParameters($request = null) {
        $this->parameters->name = "Přehled předmětů";
        return $this->parameters;
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \BakaParser\Response
     */
    public function parse($request) {
        $rows = $request->filterXPath("//*[@class='dxrp dxrpcontent']//tr");

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

        return $this->response->setResult($lessons);
    }

}

?>