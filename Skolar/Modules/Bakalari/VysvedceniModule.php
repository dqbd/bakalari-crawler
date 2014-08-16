<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class VysvedceniModule extends \Skolar\Modules\BaseModule {

    public function defineParameters($context = null) {
        parent::defineParameters($context);
            
        $this->parameters->url = BakalariToolkit::assignUrl("Pololetní klasifikace", $context["navigace"]);
    }
    

    public function parse($content = null) {

        $rows = $content->getDom()->filterXPath("//*[@class='dxrp dxrpcontent']//tr");
        $data = array('vysvedceni' => array());

        $data['rocniky'] = $rows->eq(0)->filterXPath("./*/td[@class='polonadpis2']")->extract("_text");
        $data['predmety'] = array();

        foreach ($rows as $n => $row) {

            if ($n > 1) {
                $row = new Crawler($row);

                $name = trim($row->filterXPath("//*[@class='polonazev']")->text());

                if (($names_id = array_search($name, $data['predmety'])) === false) {
                    $data['predmety'][] = $name;
                    end($data['predmety']);

                    $names_id = key($data['predmety']);
                }

                $marks = $row->filterXPath("./*/td[@class='poloznamka']");

                foreach ($marks as $x => $mark) {
                    $mark = new Crawler($mark);

                    $year = (($x + 2) % 2 == 0) ? ($x + 2) / 2 : ($x + 1) / 2;
                    $half = ($x % 2 == 0) ? 1 : 2;
                    $data['vysvedceni'][$year][$half][$names_id] = trim($mark->text());
                }
            }
        }

        return $this->getResponse()->setResult($data);
    }

}

?>