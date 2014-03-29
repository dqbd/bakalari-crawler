<?php

namespace BakaParser\Handlers;

use \Symfony\Component\DomCrawler\Crawler;

class BakalariHandler implements \BakaParser\Handlers\BaseHandler {

    /** @var \BakaParser\Modules\BaseModule */
    private $module = null;
    
    /** @var mixed[] */
    private $params = null;
    
    /** @var \Guzzle\Http\Client HTTP klinet */
    private $client = null;
    
    /** @var \BakaParser\Modules\Bakalari\LoginModule Přihlašování  */
    private $login = null;
    
    /** @var \BakaParser\Modules\Bakalari\NavigaceModule Navigace  */
    private $nav = null;
    
    protected $cookie = "";

    /**
     * @param \BakaParser\Modules\BaseModule $module
     * @param mixed[] $parameters
     */
    public function __construct(\BakaParser\Modules\BaseModule $module, $parameters) {
        $this->login = new \BakaParser\Modules\Bakalari\LoginModule();
        $this->nav = new \BakaParser\Modules\Bakalari\NavigaceModule();
        
        if($module == $this->login) { 
            $this->module = $this->login;
        } else if ($module == $this->nav) {
            $this->module = $this->nav;
        } else {
            $this->module = $module;
        }
        
        $this->params = $parameters;
    }

    /**
     * Vyjede výsledek
     * 
     * @return \BakaParser\Handlers\Response
     */
    public function output() {
        return (empty($this->params["file"])) ? $this->outputHttp() : $this->outputFile();
    }
    
    /**
     * Filtruje data z existujícího souboru
     * 
     * @return \BakaParser\Handlers\Response
     */
    private function outputFile() {
        $response = new Crawler("");
        $response->addHtmlContent(file_get_contents("tests/" . $this->params['file']));

        return $this->module->parse($response, $this->params);
    }
    
    /**
     * Stáhne a filtruje data pomocí HTTP požadavku
     * 
     * @return \BakaParser\Handlers\Response
     */
    private function outputHttp() {
        try {
            $this->client = new \Guzzle\Http\Client($this->params['url']);

            $this->cookie = $this->getCacheCookie(
                $this->params['user'], $this->params['pass'], $this->params['url']
            );

            $cookieJar = new \Guzzle\Plugin\Cookie\CookieJar\FileCookieJar($this->cookie);
            $cookiePlugin = new \Guzzle\Plugin\Cookie\CookiePlugin($cookieJar);
            $this->client->addSubscriber($cookiePlugin);

            //načteme stránku, když nejsme ještě přihlášeni (vrací to samé, když cheme přihlašovat
            $response = $this->prepPage();
            
            if($response instanceof \BakaParser\Response) {
                return $response;
            }
            
            //get prerequisites
            $module_params = $this->module->getParameters(array($this->params, $response));
            
            //neznáme URL? chceme ho tedy zjistit
            if(empty($module_params->url)) {
                $module_params->url = $this->utilizeNav($response, $module_params->name);
            }
            
            if($module_params->url == false){
                return (new \BakaParser\Response())->setError("Neexistující požadavek");
            }
            
            $response = $this->loadPage($module_params->url);
            
            //just load prerequisites...
            if (!empty($module_params->optional) || !empty($module_params->required)) {
                $response = $this->fillParameters($response, $module_params);
            }

            return $this->module->parse($response, $this->params);
            
        } catch (\Guzzle\Http\Exception\CurlException $e) {
            return (new \BakaParser\Response())->setError("Nemohu se připojit na server");
        } 
    }
    
    /**
     * Získat navigaci a náš požadovaný odkaz
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $response
     * @return string|false Cílový odkaz
     */
    private function utilizeNav($response, $page_name) {
        $links = $this->nav->parse($response, $this->params)->getData()["navigace"];
        $url = array_search(strtolower($page_name), array_map('strtolower', $links));
        
        
        return ($url !== false) ? $url : false;
    }
    
    /**
     * Načíst a přihlásit se při HTTP požadavku
     * 
     * @return mixed
     */
    private function prepPage() {
        $request = $this->client->get("uvod.aspx"); //načteme úvod, zda jsme přihlášeni

        $response = new Crawler("", $this->params['url']);
        $response->addHtmlContent($request->send()->getBody(true));

        
        //nejsme přihlášeni, nebo si chceme ověřit, zda se dá přihlhlásit...
        if (get_class($this->module) != get_class($this->login) && $this->login->isLogin($response)) {
            
            $response = $this->fillParameters($response, 
                $this->login->getParameters(array($this->params, $response)) ///aka login.aspx
            );

            $result = $this->login->parse($response, $this->params);

            if ($result->getStatus() != true) { //nejsme přihlášeni.
                unset($this->cookie);
                return $result;
            }
        }
        
        
        return $response;
    }
    
    /**
     * Načti webovou stránku
     * 
     * @param string $page
     * @param mixed[] $arguments
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    private function loadPage($page, $arguments = array()) {
        $crawler = new Crawler("", $this->params["url"]);
        
        if(empty($arguments)) { //is a GET
            $request = $this->client->get($page);
        } else {
            $request = $this->client->post($page, array(), $arguments);
        }
        
        $crawler->addHtmlContent($request->send()->getBody(true));
        
        return $crawler;
    }

    /**
     * Vyplň požadované údaje
     * 
     * @param \Symfony\Component\DomCrawler\Crawler $response
     * @param \BakaParser\Parameters $module_params
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    private function fillParameters($response, $module_params) {
        
        try {
            $arguments = $response->filterXPath("//form")->form($module_params->optional)->getValues();

            //add possible button values
            foreach ($response->filterXPath("//*[self::input and @value and @name and (@type='button' or @type='submit' or @type='image')]") as $button) {
                $arguments[$button->getAttribute("name")] = $button->getAttribute("value");
            }

            //force-arguments
            $arguments = array_merge($module_params->required, $arguments);


            $response = $this->loadPage($module_params->url, $arguments);
        } catch (\InvalidArgumentException $e) {
            
        }

        return $response;
    }

    /**
     * Vytvoř a získej soubor cookie
     * 
     * @param string $user
     * @param string $pass
     * @param string $url
     * @return string
     */
    private function getCacheCookie($user, $pass, $url) {
        $url = hash("sha256", $url);
        $path = "cache/" . $url . "/" . hash("sha256", $user . "BPRKYSFTY" . $pass);

        if (!file_exists("cache/" . $url)) {
            mkdir("cache/" . $url, 0777, true);
        }
        touch($path);

        $path = realpath($path);
        return $path;
    }
}

?>