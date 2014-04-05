<?php

namespace BakaParser\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class UkolyModule extends \BakaParser\Modules\BaseModule {

    /**
     * 
     * @param mixed[] $request
     * @return \BakaParser\Parameters
     */
    public function getParameters($request = null) {
        $this->parameters->name = "Domácí úkoly";
        return $this->parameters;
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \BakaParser\Response
     */
    public function parse($request) {
        

        $data = $request->filterXPath("//*[@class='ukoltab']//tr");

        $ukoly = array("ukoly" => array());

        $year = explode("/", substr($request->filterXPath("//*[@class='pololetinadpis']")->text(), -7));
        $year[1] = "20" . $year[1];

        foreach ($data as $row) {
            $row = new Crawler($row);

            $date = $row->filterXPath("//*[@class='ukoldatdo']")->text();
            $date = explode(".", substr($date, 0, strlen($date) - 1)); //extrakce data z formátu dd.mm

            $date[] = ($date[1] >= 9) ? $year[0] : $year[1]; //zjišťujeme rok

            

            $date = strtotime(implode(".", $date)); //skládáme zpět a konvertujeme na unix

            $subject = $row->filterXPath("//*[@class='ukoldatod']")->text();
            $detail = $row->filterXPath("//td")->last()->text();

            $ukoly["ukoly"][] = array("date" => $date, "subject" => $subject, "detail" => $detail);
        }



        return $this->response->setResult($ukoly);
    }

}

?>