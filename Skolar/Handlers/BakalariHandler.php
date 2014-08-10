<?php
namespace Skolar\Handlers;

use \Symfony\Component\DomCrawler\Crawler;

class BakalariHandler implements \Skolar\Handlers\BaseHandler {

	protected $module = null;
	protected $params = null;

	private $client = null;
	private $cache = null;

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
			$this->initHttp();	

			if($module->defineParameters() === false) { 
				if($module->getUrl() !== false) {
				}
			}

		}
		// print_r($flow);
		// if($this->module->getName() == "batch") {
		// }

		// echo $this->module->getName();
	}

	public function receiveContent() {

	}

	public function outputHttp() {
		$url = Skolar\Utils::getFixedUrl($this->params->get('url'));
		$username = $this->params->get('user');
		$password = $this->params->get('pass');

		$this->initHttp($username, $password, $url);
	}

	public function initHttp($user, $pass, $url) {
		$this->client = new \Guzzle\Http\Client($url);
		
		$cookiePath = $this->getCookieCachePath($user, $pass, $url);

		$this->cache = new \Skolar\CacheStorage($cookiePath);
		$this->client->addSubscriber((new \Guzzle\Plugin\Cookie\CookiePlugin($this->cache)));
	}

	public function outputLocal() {

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


	private function destroyCookieCache($user, $pass, $url) {
		$path = $this->getCookieCachePath($user, $pass, $url);

		unset($path);
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