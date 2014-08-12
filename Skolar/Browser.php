<?php
namespace Skolar;

class Browser {
	private $client = null;
	public $cache = true;

	public $baseurl = "";

	private $callback = null;

	private $results;

	public function __construct($baseurl = "", $cookiepath = null) {
		$this->cache = ($cookiepath === null) ?: new \Skolar\Browser\CacheStorage($cookiepath);

		$this->client = new \GuzzleHttp\Client(array(
			"base_url" => (!empty($baseurl)) ? Utils::getBaseUrl($baseurl) : "",
			"defaults" => array(
				"cookies" => $this->cache,
				"headers" => Configuration::get("browser_headers")
			)
		));

		$this->results = new \Skolar\Browser\PageStorage();
	}

	public function load($urls, $parameters = array()) {
		$requests = $this->getRequests($urls, $parameters);
		$responses = \GuzzleHttp\batch($this->client, $requests);

		foreach($responses as $request) {
			$this->results[] = $this->handleResponse($request, $responses[$request]);
		}

		return $this->results;
	}

	public function handleResponse($request, $class) {
		$response = !($class instanceof \GuzzleHttp\Message\ResponseInterface) ? $class->getResponse() : $class;

		return new \Skolar\Browser\PageData(
			$this->client->getBaseUrl(), 
			$request->getUrl(), 
			$response->getBody(), 
			$response->getStatusCode(), 
			$response->getEffectiveUrl(),
			($class instanceof \RuntimeException) ? $class->getMessage() : false
		);
	}

	public function getRequests($urls, $parameters, $send_local = false) {
		$urls = (is_array($urls)) ? $urls : array($urls);
		$requests = array();

		foreach($urls as $url) {
			// if(($path = $this->checkFileExists($url)) !== false) {
			// 	$local = array(
			// 		"code" => 0,
			// 		"body" => file_get_contents($path),
			// 		"final_url" => $path,
			// 		"error" => false
			// 	);

			// 	if($send_local == false) {
			// 		$this->local_results[$url] = $local;
			// 	} else {
			// 		$this->runCallback($local);
			// 	}

			// 	continue;
			// }

			$requestparams = null;

			if (count($parameters) == count($parameters, COUNT_RECURSIVE) && isset($parameters[$url])) {
				$requestparams = $parameters[$url];
			} else {
				$requestparams = $parameters;
			}

			$method = empty($requestparams) ? "GET" : "POST";
			$requests[] = $this->client->createRequest($method, $url, array("body" => $requestparams));
		}

		return $requests;
	}

	private function tryInterceptRequest($target, $parameters) {
		if(($path = $this->checkFileExists($target)) !== false) {

		} 
	}


	public function checkFileExists($target) {
		foreach(scandir(SKOLAR_TESTFILES_DIR) as $dir) {
			if(is_dir(SKOLAR_TESTFILES_DIR . DIRECTORY_SEPARATOR . $dir)) {
				foreach(scandir(SKOLAR_TESTFILES_DIR . DIRECTORY_SEPARATOR . $dir) as $file) {
					if($file == $target) {
						return join(DIRECTORY_SEPARATOR, array(SKOLAR_TESTFILES_DIR, $dir, $file));
					}
				}
			}
		}

		return false;
	}

	public function getCache() {
		return ($this->cache != false) ? $this->cache : null;
	}

	public function getFullUrl($part) {
		return $this->clinet->getBaseUrl() . "/" . $part;
	}
}

?>