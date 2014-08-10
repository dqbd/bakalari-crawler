<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class ZnamkyModule extends \Skolar\Modules\BaseModule {

    /**
     * 
     * @return \Skolar\Parameters
     */
    public function defineParameters($context = null) {
        parent::defineParameters();

        $this->parameters->label = "Průběžná klasifikace";
        $this->parameters->optional = array(
            'ctl00$cphmain$Checkdetail' => true,
            'ctl00$cphmain$Flyout2$Checkdatumy' => true,
            'ctl00$cphmain$Flyout2$Checktypy' => true
        );
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \Skolar\Response
     * 
     */
    public function parse($content = null) {
        
        $rows = $content->filterXPath("//*[@class='dxrp dxrpcontent']//tr");
        $znamky = array();
        
        $last = "";

        foreach ($rows as $row) {
            $row = new Crawler($row);
            $fields = $row->children();

            $name = trim($fields->eq(0)->text());
            $last = ($last != $name && !empty($name)) ? $name : $last;
            $name = (empty($name)) ? $last : $name;
            
            
            $mark = $this->reformat($fields->eq(1)->text());
            
            $max_points = $fields->eq(1)->filterXPath("//span[@class='znmaxbody']");
            if(count($max_points) > 0) {
                $max_points = $this->reformat($max_points->text());
                
                $gain = substr($mark, 0, strlen($mark) - strlen($max_points)); 
                
                
                $mark = array(
                    "gain" => $gain,
                    "max" => $max_points
                ); 
            } 

            $date = $row->filterXPath("./*/td[@class='detdatum']");
            $weight = $row->filterXPath("./*/td[@class='detvaha']");
            
            $znamky[$name][] = array(
                "mark" => $mark,
                "caption" => $this->reformat($fields->eq(2)->text()),
                "date" => (count($date) > 0) ? $date->text() : "", //optional, ne všechny školy zobrazují datum
                "note" => $this->reformat($row->filterXPath("./*/td[@class='detpozn2']")->text()),
                "weight" => (count($weight) > 0) ? $this->reformat($weight->text(), ["váha"]) : "1"
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

    private function reformat($inp, $additional = array()) {
        $replacing = array_merge(array("(", ")"), $additional);
        
        return ucfirst(str_replace($replacing, "", trim($inp)));
    }

}

?>