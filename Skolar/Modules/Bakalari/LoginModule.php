<?php

namespace Skolar\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class LoginModule extends \Skolar\Modules\BaseModule {

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $context
     * @return \Skolar\Parameters
     */
    public function defineParameters($context = null) {
        parent::defineParameters();

        $this->parameters->url = "login.aspx";

        if($context instanceof Crawler && $this->isLogin($context)) {
            $keys = array('ctl00$cphmain$TextBoxjmeno', 
                'ctl00$cphmain$TextBoxHeslo', 
                'ctl00$cphmain$checkstale');
            
            $new_syntax = $context->filterXPath("//*[contains(@id, 'dxss') and "
                    . "text()[contains(.,'ASPxClientTextBox')]]");

            if(count($new_syntax) == 2) {
                //split the names from them
                $keys = array_replace($keys, array_map(function($e) { 
                    preg_match("/dxo\.uniqueID\s*=\s*\'(.*)\';/", $e, $match);
                    return $match[1];
                }, $new_syntax->extract(array("_text"))));
            } 

            $this->parameters->optional = array_combine(
                $keys, array($arg['user'], $arg['pass'], true)
            );
        } else {
            return "dom";
        }
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $content
     * @return \Skolar\Response
     */
    public function parse($content = null) {
        $name = trim($content->filterXPath("//*[@class='logjmeno']")->text());

        if (strtolower($name) == "nepřihlášen") {

            $error_dom = $content->filterXPath('//*[@id="cphmain_LabelChyba"]');
            $error = (count($error_dom) > 0) ? array(
                "short" => $error_dom->text(),
                "long" => $error_dom->attr("title")
            ) : null;

            $this->response->setError('Failed to login', $error);
        } else {
            $result = array();

            $result['name'] = $name;
            $result['type'] = $content->filterXPath("//table[@class='logtable']//tr[1]/td[2]")->text();

            $this->response->setResult($result);
        }

        return $this->response;
    }

    public function isLogin(Crawler $dom) {
        $login_el = $dom->filterXPath('//*[@name="ctl00$cphmain$TextBoxjmeno" or @name="ctl00$cphmain$TextBoxHeslo" or @name="ctl00$cphmain$ButtonPrihlas"]');
        
        if(count($login_el) < 3) {
            $login_el = $dom->filterXPath("//*[contains(@id, 'dxss') and (text()[contains(.,'ASPxClientTextBox')] or text()[contains(., 'ASPxClientButton')])]");
            return count($login_el) == 3;
        } 
        
        return count($login_el) == 3;
    }

    public function postParse($content) {

    }

}

?>