<?php

namespace BakaParser\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class SuplovaniModule extends \BakaParser\Modules\BaseModule {

    /**
     * 
     * @param mixed[] $request
     * @return \BakaParser\Parameters
     */
    public function getParameters($request = null) {
        $optional = array();
        if (!empty($request[0]['arg'])) {
            $options = array(1 => "suplování na tento týden", 2 => "suplování na příští týden");
            $optional['ctl00$cphmain$radiosuplov'] = $options[$request[0]['arg']];
        }
        $this->parameters->optional = $optional;
        $this->parameters->name = "Suplování";

        return $this->parameters;
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \BakaParser\Response
     */
    public function parse($request) {
        $data = $request->filterXPath("//*[@class='dxrp dxrpcontent']//div[(@class='supden' or @class='suphod') and text() != 'Žádné změny']");

        $year = explode("/", substr($request->filterXPath("//*[@class='pololetinadpis']")->text(), -7));
        $year[1] = "20" . $year[1];

        $suplovani = array("suplovani" => array());
        $last = "";

        foreach ($data as $item) {
            $text = $item->nodeValue;

            if ($item->getAttribute("class") == "supden") {
                $text .= (explode(".", $text)[1] >= 9) ? $year[0] : $year[1];
                $text = strtotime(mb_substr($text, 2, mb_strlen($text), 'UTF-8'));

                if (empty($last) || $last["date"] != $text) {
                    if ($last != null) {
                        $suplovani["suplovani"][] = $last;
                    }

                    $last = array("date" => $text, "changes" => array());
                }
            } else {
                $last["changes"][] = $text;
            }
        }

        if (!empty($last)) {
            $suplovani["suplovani"][] = $last;
        }

        return $this->response->setResult($suplovani);
    }

}

?>