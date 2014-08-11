<?php
namespace Skolar;

class Browser {
	private $client = null;
	public $cache = true;

	public $baseurl = "";

	private $callback = null;

	private $local_results = array();

	public function __construct($baseurl = "", $cookiepath = null, $callback = null) {
		if($cookiepath !== null) {
			$this->cache = new \Skolar\CacheStorage($cookiepath);
		}

		$this->baseurl = (filter_var($baseurl, FILTER_VALIDATE_URL) !== false) ? Tools::getBaseUrl($baseurl) : "";
		$this->cookiepath = $cookiepath;

		$this->callback = $callback;
	}

	/**
	 * Načte odkaz pararelně a vyčká až všechny požadavky jsou hotové
	 * 
	 **/
	public function load($urls, $parameters = array()) {
		$requests = $this->getRequests($urls, $parameters);
		$responses = \GuzzleHttp\batch($this->client(), $requests);
		$result = array();

		foreach($responses as $request) {
			$result[$request->getUrl()] = $this->handleResponse($responses[$request]);
		}

		$this->runCallback($result);
	}

	/**
	 * Načte odkazy pararelně a spouští ihned callback když je jeden hotov
	 * 
	 **/
	public function loadParallel($urls, $parameters = array()) {
		$requests = $this->getRequests($urls, $parameters, true);

		$this->client->sendAll($requests, array(
			"complete" => array($this, "handleEventResponse"),
			"error" => 	array($this, "handleEventResponse")	
		));
	}

	/**
	 * Načte odkazy seriálně
	 * 
	 **/ 
	public function loadBatch($urls, $parameters = array()) {
		$requests = $this->getRequests($urls, $parameters);
		$result = array();
		foreach($requests as $request) {
			$result[$request->getUrl()] = $this->handleResponse($this->client->send($request));
		}

		$this->runCallback($result);
	}


	public function handleEventResponse($event) {
		$this->runCallback(array($event->getRequest()->getUrl() => $this->handleResponse($event)));
	}

	public function handleResponse($class) {
		if(!($class instanceof \GuzzleHttp\Message\ResponseInterface)) {
			$class = !($class instanceof \GuzzleHttp\Event\ErrorEvent) ? $class : $class->getException();

			$response = $class->getResponse();
		} else {
			$response = $class;
		}

		$data = array(
			"code" => $response->getStatusCode(),
			"body" => $response->getBody(),
			"final_url" => $response->getEffectiveUrl(),
			"error" => ($class instanceof \RuntimeException) ? $class->getMessage() : false 
		);

		return $data;
	}

	public function runCallback($data) {
		call_user_func($this->callback, array_merge($this->local_results, $data));
	}

	public function getRequests($urls, $parameters, $send_local = false) {
		$urls = (is_array($urls)) ? $urls : array($urls);
		$requests = array();

		foreach($urls as $url) {
			if(($path = $this->checkFileExists($url)) !== false) {
				$local = array(
					"code" => 0,
					"body" => file_get_contents($path),
					"final_url" => $path,
					"error" => false
				);

				if($send_local == false) {
					$this->local_results[$url] = $local;
				} else {
					$this->runCallback($local);
				}

				continue;
			}

			$requestparams = null;

			if (count($parameters) == count($parameters, COUNT_RECURSIVE) && isset($parameters[$url])) {
				$requestparams = $parameters[$url];
			} else {
				$requestparams = $parameters;
			}

			$method = empty($requestparams) ? "GET" : "POST";
			$requests[] = $this->client()->createRequest($method, $url, array("body" => $requestparams));
		}

		return $requests;
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

	public function client() {
		if($this->client === null) {
			$this->client = new \GuzzleHttp\Client(array(
				"base_url" => $this->baseurl,
				"defaults" => array(
					"cookies" => $this->cache,
					"headers" => Configuration::get("browser_headers")
				)
			));
		}

		return $this->client;
	}

	public function getCache() {
		return ($this->cache != false) ? $this->cache : null;
	}
}

?>