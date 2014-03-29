<?php

namespace BakaParser\Modules\Bakalari;

use \Symfony\Component\DomCrawler\Crawler;

class LoginModule extends \BakaParser\Modules\BaseModule {

    /**
     * 
     * @param mixed[] $request
     * @return \BakaParser\Parameters
     */
    public function getParameters($request = null) {
        list($arg, $dom) = $request;
        
        $keys = array('ctl00$cphmain$TextBoxjmeno', 
            'ctl00$cphmain$TextBoxHeslo', 
            'ctl00$cphmain$checkstale');
        
        $new_syntax = $dom->filterXPath("//*[contains(@id, 'dxss') and "
                . "text()[contains(.,'ASPxClientTextBox')]]");
        
        if(count($new_syntax) == 2) {
            //split the names from them
            $keys = array_replace($keys, array_map(function($e) { 
                preg_match("/dxo\.uniqueID\s*=\s*\'(.*)\';/", $e, $match);
                return $match[1];
            }, $new_syntax->extract(array("_text"))));
        }
        
        $this->parameters->url = "login.aspx";
        $this->parameters->optional = array_combine($keys, array($arg['user'], $arg['pass'], true));
        
        return $this->parameters;
        
    }

    /**
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $request
     * @return \BakaParser\Response
     */
    public function parse($request) {
        $name = trim($request->filterXPath("//*[@class='logjmeno']")->text());

        if (strtolower($name) == "nepřihlášen") {

            $error_dom = $request->filterXPath('//*[@id="cphmain_LabelChyba"]');
            $error = (count($error_dom) > 0) ? array(
                "short" => $error_dom->text(),
                "long" => $error_dom->attr("title")
            ) : null;

            $this->response->setError('Failed to login', $error);
        } else {
            $result = array();

            $result['name'] = $name;
            $result['type'] = $request->filterXPath("//table[@class='logtable']//tr[1]/td[2]")->text();

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

}

?>