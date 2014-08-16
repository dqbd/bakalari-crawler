<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class UkolyModule extends \Skolar\Modules\BaseModule {

    public function getParameters($context = null) {
        parent::defineParameters($context);

        $this->parameters->url = BakalariToolkit::assignUrl("Domácí úkoly", $context["navigace"]);
    }

    public function parse($content = null) {
        $data = $content->getDom()->filterXPath("//*[@class='ukoltab']//tr");

        $ukoly = array("ukoly" => array());
        foreach ($data as $row) {
            $row = new Crawler($row);

            $ukoly["ukoly"][] = array(
                "date" => BakalariToolkit::getDate($row->filterXPath("//*[@class='ukoldatdo']")->text()), 
                "subject" => trim($row->filterXPath("//*[@class='ukoldatod']")->text()), 
                "detail" => trim($row->filterXPath("//td")->last()->text())
            );
        }

        return $this->getResponse()->setResult($ukoly);
    }

}

?>