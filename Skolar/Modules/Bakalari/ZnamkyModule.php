<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class ZnamkyModule extends \Skolar\Modules\BaseModule {

    public function defineParameters($context = null) {
        parent::defineParameters($context);

        $this->parameters->url = BakalariToolkit::assignUrl("Průběžná klasifikace", $context["navigace"]);

        if(isset($context["pagedata"]) && $context["pagedata"] instanceof \Skolar\Browser\PageData) {
            $keys = array('ctl00$cphmain$Checkdetail', 'ctl00$cphmain$Flyout2$Checkdatumy', 'ctl00$cphmain$Flyout2$Checktypy');
            $values = array();

            //novější verze Bakaláří mají jiný checkbox
            foreach($keys as $item) {
                if(count($context["pagedata"]->getDom()->filterXPath(sprintf('//*[@name="%s" and @type="text"]', $item))) > 0) {
                    $values[] = "C";
                } else {
                    $values[] = true;
                }
            }

            $this->parameters->formparams = BakalariToolkit::getFormParams($context, array_combine($keys, $values));
        } 
    }

    public function parse($content = null) {
        $rows = $content->getDom()->filterXPath("//*[@class='dettable']//tr");

        if(count($rows) == 0) {
            $rows = $content->getDom()->filterXPath("//*[@class='dxrp dxrpcontent']//tr");
        }

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

            $note = $row->filterXPath("./*/td[@class='detpozn2']");
            
            $znamky[$name][] = array(
                "mark" => $mark,
                "caption" => $this->reformat($fields->eq(2)->text()),
                "date" => (count($date) > 0) ? BakalariToolkit::getDate($date->text()) : "", //optional, ne všechny školy zobrazují datum
                "note" => (count($note) > 0) ? $this->reformat($note->text()) : "",
                "weight" => (count($weight) > 0) ? $this->reformat($weight->text(), ["váha"]) : "1"
            );
        }


        foreach ($znamky as &$predmet) {
            $temp = $predmet; //hacky, ale nějak to nejde

            usort($predmet, function($a, $b) {
                return $a["date"] - $b["date"];
            });

            $predmet = $temp;
        }

        $znamky = (empty($znamky)) ? null : array(
            "predmety" => array_keys($znamky),
            "znamky" => array_values($znamky)
        );


        return $this->getResponse()->setResult($znamky);
    }

    private function reformat($inp, $additional = array()) {
        $replacing = array_merge(array("(", ")"), $additional);
        
        return ucfirst(str_replace($replacing, "", trim($inp)));
    }

}

?>