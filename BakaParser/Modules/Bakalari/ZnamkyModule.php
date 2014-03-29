<?php

namespace BakaParser\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class ZnamkyModule extends \BakaParser\Modules\BaseModule {

    /**
     * 
     * @param mixed[] $request
     * @return \BakaParser\Parameters
     */
    public function getParameters($request = null) {

        $this->parameters->name = "Průběžná klasifikace";
        $this->parameters->optional = array(
            'ctl00$cphmain$Checkdetail' => true,
            'ctl00$cphmain$Flyout2$Checkdatumy' => true
        );
        
        return $this->parameters;
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \BakaParser\Response
     * 
     */
    public function parse($request) {
        
        $rows = $request->filterXPath("//*[@class='dxrp dxrpcontent']//tr");
        $znamky = array();
        
        $last = "";

        foreach ($rows as $row) {
            $row = new Crawler($row);
            $fields = $row->children();

            $name = trim($fields->eq(0)->text());

            $last = ($last != $name && !empty($name)) ? $name : $last;

            $name = (empty($name)) ? $last : $name;

            $date = $row->filterXPath("./*/td[@class='detdatum']");

            $znamky[$name][] = array(
                "mark" => $this->reformat($fields->eq(1)->text()),
                "caption" => $this->reformat($fields->eq(2)->text()),
                "date" => (count($date) > 0) ? $date->text() : "", //optional, ne všechny školy zobrazují datum
                "note" => $this->reformat($row->filterXPath("./*/td[@class='detpozn2']")->text())
            );
        }


        foreach ($znamky as &$predmet) {
            $temp = $predmet; //hacky, ale nějak to nejde

            usort($predmet, function($a, $b) {
                return strtotime($a["date"]) - strtotime($b["date"]);
            });

            $predmet = $temp;
        }

        $znamky = (empty($znamky)) ? null : array(
            "predmety" => array_keys($znamky),
            "znamky" => array_values($znamky)
        );


        return $this->response->setResult($znamky);
    }

    private function reformat($inp) {
        return ucfirst(str_replace(array("(", ")"), "", trim($inp)));
    }

}

?>