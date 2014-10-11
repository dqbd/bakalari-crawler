<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class RozvrhModule extends \Skolar\Modules\BaseModule {

    public function defineParameters($context = null) {
        parent::defineParameters($context);

        $this->parameters->url = BakalariToolkit::assignUrl("Rozvrh", $context["navigace"]);

        if($this->getRequestParam("view")) {
           $this->parameters->formparams = BakalariToolkit::getFormParams($context, array('ctl00$cphmain$radiorozvrh' => $this->getRequestParam("view")));
        } else {
           $this->parameters->formparams = array();
        }
    }

    public function parse($content = null) {
        $dom = $content->getDom();

        $rozvrh = (count($dom->filterXPath("//*[@class='r_roztable']")) == 1) ? 
            $this->parse_new($dom) : //nový syntax
            $this->parse_old($dom); //starý syntax

        //TODO: kalendář
        $rozvrh["views"] = array_slice(
                $dom->filterXPath('//select[@name="ctl00$cphmain$radiorozvrh"]/option')
                ->extract(["_text", "value"]), 0, 3);
        
        array_walk($rozvrh["views"], function(&$item) {
            $item = array_combine(["label", "value"], $item);
        });
        
        return $this->getResponse()->setResult($rozvrh);
    }
    

    private function parse_new(Crawler $dom) {
        $rozvrh = array("casy" => array(), "rozvrh" => array());

        $dom = $dom->filterXPath("//*[@class='r_roztable']/*/tr");

        $cells_first = $dom->eq(0)->filterXPath("./*/td//div[@class='r_cislohod']")->extract("_text");
        $cells_second = $dom->eq(0)->filterXPath("./*/td//div[@class='r_popishod']")->extract("_text");

        foreach ($cells_first as $x => $value) {
            if ($value != chr(0xC2) . chr(0xA0)) {
                $rozvrh['casy'][] = array("label" => $value, "time" => explode(" - ", $cells_second[$x]));
            }
        }

        foreach ($dom as $n => $row) {
            $skip = -1; //někdy chceme, abychom přeskočili některé hodiny, když slučujeme

            if ($n > 0) {
                $cells = (new Crawler($row))->filterXPath("./*/td");
                $day = array("day" => array(), "lessons" => array());

                foreach ($cells as $x => $cell) {
                    $cell = new Crawler($cell);
                    $cell_type = $cell->attr("class");
                    
                    if($skip == $x) {
                        continue;
                    }

                    if ($cell_type == "r_rozden") {
                        $content = $cell->filterXPath("./*/*/div"); //subchildren
                        $day['day']['label'] = $content->eq(0)->text();

                        if (count($content) > 1) {
                            $day['day']['time'] = BakalariToolkit::getDate($content->eq(1)->text());
                        }
                    } else {

                        //regex: ^r_bunka(_in[2-4]|mo|abs|zm)$|^r_bunka$
                        $cell_content = $cell->filterXPath("//div[@class='r_bunka' or 
                            @class='r_bunka_in2' or 
                            @class='r_bunka_in3' or 
                            @class='r_bunka_in4' or 
                            @class='r_bunka_in2last' or 
                            @class='r_bunka_in3last' or 
                            @class='r_bunka_in4last' or 
                            @class='r_bunkazm' or 
                            @class='r_bunkaabs' or 
                            @class='r_bunkamo']");

                        $hour = array("lesson" => $x - 1, "type" => "normal", "content" => array()); //1 hodina

                        if ($hour["lesson"] <= $skip) {
                            continue;
                        }

                        foreach ($cell_content as $content) {

                            $content = (new Crawler($content))->children();
                            $lesson = array(
                                "name" => array("short" => "", "long" => ""),
                                "teacher" => array("short" => "", "long" => ""),
                                "place" => array("short" => "", "long" => ""),
                                "group" => array("short" => "", "long" => ""),
                                "changes" => ""
                            );

                            foreach ($content as $value) {  //hodnoty
                                $value = new Crawler($value);
                                $value_class = $value->attr("class");

                                if (strpos($value_class, "predm") !== false) { //předmět
                                    $lesson['name'] = array("short" => $value->text(), "long" => $value->attr("title"));
                                } else if (strpos($value_class, "ucit") !== false) { //učitel
                                    $lesson['teacher'] = array("short" => $value->text(), "long" => $value->attr("title"));
                                } else if (strpos($value_class, "skup") !== false) { //skupina
                                    $lesson['group'] = array("short" => $value->children()->eq(0)->text(), "long" => $value->children()->eq(0)->attr("title"));
                                } else if (strpos($value_class, "mist") !== false) { //místnost
                                    $lesson['place'] = array("short" => $value->children()->eq(0)->text(), "long" => $value->children()->eq(0)->attr("title"));
                                } else if (strpos($value_class, "rinfo") !== false) { //změna
                                    $hour["type"] = "changed";
                                    $lesson["changes"] = $value->attr("title");
                                } else if (strpos($value_class, "denabs") !== false) { //denní absence
                                    //sdružení ostatních
                                    $nexts = $cell->nextAll();
                                    
                                    if (count($nexts) > 0) {
                                        foreach ($nexts as $y => $next_cell) {
                                            $next_cell = (new Crawler($next_cell))->filterXPath("//*[@class='r_denabs']");
                                            
                                            if ((count($next_cell) > 0 && $next_cell->attr("title") == $value->attr("title") && $next_cell->text() == $value->text())) {
                                                if ($y == 0) {
                                                    $hour['lesson'] = array("begin" => $hour['lesson'], "length" => 2);
                                                } else {
                                                    $hour['lesson']['length'] ++;
                                                }
                                                $skip = $x - 1 + ($y + 1);
                                                
                                                
                                            } else {
                                                break;
                                            }
                                        }
                                    } else if (($length = count($rozvrh['casy']) - $hour['lesson']) > 1) {
                                        $hour['lesson'] = array("begin" => $hour['lesson'], "length" => $length);
                                    }

                                    $hour["type"] = "free";
                                    $lesson['name'] = array("short" => $value->text(), "long" => $value->attr("title"));
                                }
                            }

                            $hour['content'][] = $lesson;
                        }

                        if (count($cell_content) > 0) {
                            $day['lessons'][] = $hour;
                        }
                    }
                }

                $rozvrh['rozvrh'][] = $day;
            }
        }

        return $rozvrh;
    }

    private function parse_old(Crawler $dom) {

        $rozvrh = array("casy" => array(), "rozvrh" => array());

        $dom = $dom->filterXPath("//*[@class='rozbunka']/*/tr");

        $cells_first = $dom->eq(0)->filterXPath("./*/td")->extract("_text");
        $cells_second = $dom->eq(1)->filterXPath("./*/td")->extract("_text");

        foreach ($cells_first as $x => $value) {
            if ($value != chr(0xC2) . chr(0xA0)) {
                $rozvrh['casy'][] = array("label" => $value, "time" => explode(" - ", $cells_second[$x]));
            }
        }

        foreach ($dom as $n => $row) {
            if (($n - 2) % 3 == 0) {
                $first_row = (new Crawler($row))->filterXPath("./*/td");
                $second_row = $dom->eq($n + 1)->filterXPath("./*/td");
                $third_row = $dom->eq($n + 2)->filterXPath("./*/td");

                $current = array("day" => array(), "lessons" => array());

                $empty = 1; //rozden je už spravován, proto dáváme 1

                foreach ($first_row as $x => $cell) {

                    $cell = new Crawler($cell);

                    if ($x == 0) {
                        $detail = $cell->children();

                        $current['day']['label'] = $cell->filterXPath("//text()")->text();

                        if (count($detail) == 1) {
                            $current['day']['time'] = BakalariToolkit::getDate($detail->text());
                        }
                    } else {

                        $current_lesson = array("lesson" => $x - 1, "type" => "normal", "content" => array());
                        $cell_name = $cell->attr("class");

                        if ($cell_name == "rozpredmetprazdny") {
                            $empty++;
                        } else if ($cell_name == "rozpredmet1abs") { //absence, budeme to sdružovat
                            //TODO- UPRAVIT
                            $nexts = $cell->nextAll();

                            $current_lesson["lesson"] = array("begin" => $current_lesson["lesson"], "length" => 1);
                            $current_lesson["type"] = "free";
                            $current_lesson["content"] = array("name" => $cell->text());

                            foreach ($nexts as $y => $cell_free) {
                                if (!empty($cell_free->nodeValue)) {

                                    $current["lessons"][] = $current_lesson;
                                    $current_lesson = array(
                                        "lesson" => array("begin" => $x - 1, "length" => 1),
                                        "type" => "free",
                                        "content" => array(
                                            array("name" => $cell_free->nodeValue)
                                        )
                                    );
                                } else {
                                    $current_lesson["lesson"]['length'] ++;
                                }
                            }

                            $current["lessons"][] = $current_lesson;

                            break;
                        } else {
                            $required = $x - $empty;
                            $cell_child = $cell->children();

                            if ($cell_child->attr("class") == "rozbunka2") {
                                $empty++;

                                $inherit_rows = $cell_child->eq(0)->filterXPath(".//tr");

                                foreach ($inherit_rows as $y => $lesson_row) {

                                    if ($y % 3 == 0) {
                                        $lesson_row = (new Crawler($lesson_row))->filterXPath("./*/td");
                                        $second_lesson_row = $inherit_rows->eq($y + 1)->filterXPath("./*/td");
                                        $third_lesson_row = $inherit_rows->eq($y + 2)->filterXPath("./*/td");

                                        $current_lesson['content'][] = array(
                                            "name" => array("short" => $lesson_row->children()->text(), "long" => $lesson_row->attr("title")),
                                            "teacher" => array("short" => $second_lesson_row->text(), "long" => $second_lesson_row->attr("title")),
                                            "place" => array("short" => trim($third_lesson_row->eq(1)->text()), "long" => $third_lesson_row->eq(1)->attr("title")),
                                            "group" => array("short" => $third_lesson_row->eq(0)->text(), "long" => $third_lesson_row->eq(0)->attr("title")),
                                            "changes" => ""
                                        );
                                    }
                                }
                            } else {

                                $lesson_changes = "";

                                if (count($changes = $cell_child->eq(0)->children()) == 1) {
                                    $current_lesson["type"] = "changed";
                                    $lesson_changes = $changes->attr("title");
                                }

                                if ($cell_name == "rozpredmet1mo") {
                                    $current_lesson["type"] = "free";
                                }

                                if (count($cell_child) == 1) { //odstraněná hodina
                                    $current_lesson['content'][] = array(
                                        "changes" => $lesson_changes
                                    );

                                    $empty++;
                                } else {
                                    $second_cell = $second_row->eq($required);
                                    $third_cell = array("group" => $third_row->eq($required), "place" => $third_row->eq($required + 1));

                                    $current_lesson['content'][] = array(
                                        "name" => array("short" => $cell_child->eq(1)->text(), "long" => $cell->attr("title")),
                                        "teacher" => array("short" => $second_cell->children()->text(), "long" => $second_cell->attr("title")),
                                        "place" => array("short" => $third_cell['place']->children()->text(), "long" => $third_cell['place']->attr("title")),
                                        "group" => array("short" => $third_cell['group']->children()->text(), "long" => $third_cell['group']->attr("title")),
                                        "changes" => $lesson_changes
                                    );
                                }
                            }
                            $current["lessons"][] = $current_lesson;
                        }
                    }
                }
                $rozvrh['rozvrh'][] = $current;
            }
        }


        return $rozvrh;
    }

}

?>