<?php
namespace Skolar\Handlers;

use \Symfony\Component\DomCrawler\Crawler;

class BakalariHandler implements \Skolar\Handlers\BaseHandler {

	protected $module = null;
	protected $params = null;

	private $browser = null;

	private $base_flow = array("login", "navigace");

	public function __construct(\Skolar\Modules\BaseModule $module, $parameters) {
		$this->module = $module;
		$this->params = $parameters;
	}

	public function output() {
		$input = ($this->module->getName() == "batch") ? $this->module->postParse($this->params) : array($this->module);
		$flow = array_unique(array_merge($this->base_flow, $input));

		foreach($flow as $module) {
			if(is_string($module)) {
				$module = sprintf("\\%s\\Modules\\Bakalari\\%sModule", dirname(__NAMESPACE__), $module);
				$module = new $module($this->params); //tady by bylo bezpečnější stripnout pouze ty parametry, které modul potřebuje...
			}

			$url = Skolar\Utils::getFixedUrl($this->params->get('url'));
			$user = $this->params->get('user');
			$pass = $this->params->get('pass');

			$this->browser = new \Skolar\Browser($url, $this->getCookieCachePath($user, $pass, $url), array($this, "receiveData"));

			if($module->defineParameters() === false) { 
				if($module->getUrl() !== false) {
				}
			}
		}
	}

	public function receiveData($data) {

	}

	
	public function wantsLocal() {
		return !empty($this->params["file"]);
	}

	/**
	 * Načti webovou stránku
	 * 
	 * @param string $page
	 * @param mixed[] $arguments
	 * @return \Symfony\Component\DomCrawler\Crawler
	 */
	private function loadPage($baseurl, $page, $arguments = array()) {
	    $crawler = new Crawler("", $baseurl);
	    
	    if(empty($arguments)) { //is a GET
	        $request = $this->client->get($page);
	    } else {
	        $request = $this->client->post($page, array(), $arguments);
	    }
	    
	    $crawler->addHtmlContent($request->send()->getBody(true));
	    
	    return $crawler;
	}

	/**
	 * Vytvoř a získej soubor cookie
	 * 
	 * @param string $user
	 * @param string $pass
	 * @param string $url
	 * @return string
	 */
	private function getCookieCachePath($user, $pass, $url) {
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