<?php
namespace Skolar\Handlers;

use \Symfony\Component\DomCrawler\Crawler;

class BakalariHandler implements \Skolar\Handlers\BaseHandler {

	protected $module = null;
	protected $params = null;

	private $browser = null;

	private $base_flow = array("login", "navigace");


	private $queue = array();
	protected $postparse_cache = array();

	public function __construct(\Skolar\Modules\BaseModule $module, $parameters) {
		$this->module = $module;
		$this->params = $parameters;
	}

	public function output() {

		$this->initBrowser();

		$module_requests = ($this->module->getName() == "batch") ? $this->module->postParse($this->params) : $this->module;

		//detect dupes in $module_request
		if(is_array($module_requests)) {
			$module_requests = array_filter($module_requests, function($module) {
				return !(in_array((string)$module, $this->base_flow));
			});


			$module_requests = (!empty($module_requests)) ? $module_requests : null;
		} else if (in_array($module_requests, $this->base_flow)) {
			$module_requests = null;
		}
		

		$flow = array_filter(array_merge($this->base_flow, array($module_requests)));
		$this->queue = \Skolar\Utils::flattenArray($flow);


		foreach($flow as $part) {
			if(!is_array($part)) {
				if(is_string($part)) {
					$part = \Skolar\Dispatcher::createModule($part, "bakalari", $this->params);
				} 

				if($part->defineParameters() !== null) {

					switch($part->defineParameters()) {
						case "dom":
							$this->browser->load($part->getUrl(), array(), function($data) {
								print_r($data);
							});
							break;
						case "url":
							break;
					}

				}
			}
		}
		//cycle through modules
		// for($i = 0; $i < count($flow); $i++) {
		// 	$module = $flow[$i];

		// 	if(is_string($module)) {
		// 		//figure batch requests
		// 		$batch = array(\Skolar\Dispatcher::createModule($module, "bakalari", $this->params));

		// 		for(;$i < count($flow);$i++) {
		// 			if(!is_string($flow[$i])) {
		// 				$i--;
		// 				break;
		// 			} else if ($module != $flow[$i]) {
		// 				$batch[] = \Skolar\Dispatcher::createModule($flow[$i], "bakalari", $this->params);
		// 			}
		// 		}

		// 		$links = array();

		// 		foreach($batch as $module) {
		// 			if($module->getUrl() !== false) {
		// 				$links[] = $module->getUrl();
		// 			}
		// 		}

		// 		$this->browser->loadBatch($links);
				
		// 	} else {

		// 	}
		// }

		// foreach($flow as $modules) {
		// 	if(is_string($modules)) {
		// 		$modules = sprintf("\\%s\\Modules\\Bakalari\\%sModule", dirname(__NAMESPACE__), $modules);
		// 		$modules = array(new $modules($this->params)); //tady by bylo bezpečnější stripnout pouze ty parametry, které modul potřebuje...
		// 	}

		// 	foreach($modules as $module) {
		// 	}

		// 	if($module->defineParameters() !== null) { 
		// 		if(!isset($module_params[$module->defineParameters()])) {

		// 		}
		// 	}
		// }
	}



	public function receiveData($data) {
		print_r($data);
	}

	public function initBrowser() {
		if(empty($this->params["file"])) {
			$url = \Skolar\Utils::getBaseUrl($this->params->get('url'));
			$user = $this->params->get('user');
			$pass = $this->params->get('pass');

			$cachepath = $this->getCookieCachePath($user, $pass, $url);
		} else {
			$cachepath = true;
		}

		$this->browser = new \Skolar\Browser($url, $cachepath, array($this, "receiveData"));
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