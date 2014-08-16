<?php
namespace Skolar;

class Browser {
	protected $client = null;
	protected $cache = true;

	protected $results;

	public function __construct($baseurl = "", $cookiepath = null) {
		$this->cache = ($cookiepath === null) ? $cookiepath : new \Skolar\Browser\CacheStorage($cookiepath);

		$this->client = new \GuzzleHttp\Client(array(
			"base_url" => (!empty($baseurl)) ? Utils::getBaseUrl($baseurl) : "",
			"defaults" => array(
				"cookies" => $this->cache,
				"headers" => Configuration::get("browser_headers")
			)
		));

		$this->results = new \Skolar\Browser\PageStorage();
	}

	public function load($urls, $parameters = array(), $cached = false) {
		$requests = $this->getRequests($urls, $parameters, $cached);
		$responses = \GuzzleHttp\batch($this->client, $requests);

		foreach($responses as $request) {
			$response = $this->handleResponse($request, $responses[$request]);
			$this->results[$response->getUrl("begin")] = $response;
		}

		return $this->results;
	}

	public function loadSingle($url, $parameters = array(), $cached = false) {
		return $this->load($url, $parameters, $cached)->get($url);;
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

	public function getRequests($urls, $parameters, $load_cached) {
		$urls = (is_array($urls)) ? $urls : array($urls);
		$requests = array();

		foreach($urls as $url) {
			$requestparams = null;


			if (count($parameters) != count($parameters, COUNT_RECURSIVE) && isset($parameters[$url])) {
				$requestparams = $parameters[$url];
			} else {
				$requestparams = $parameters;
			}


			if($this->tryInterceptRequest($url, $requestparams, $load_cached)) {
				continue;	
			}

			$method = empty($requestparams) ? "GET" : "POST";

			$requests[] = $this->client->createRequest($method, $url, array("body" => $requestparams, "cookies" => $this->cache));
		}

		return $requests;
	}

	private function tryInterceptRequest($target, $parameters, $load_cached) {
		if(($path = $this->checkFileExists($target)) !== false) {
			$this->results[$target] = new \Skolar\Browser\PageData(
				dirname($path["full_path"]), $path["filename"], file_get_contents($path["full_path"]), 300, $path["filename"], false
			);

			return true;
		} else if ($load_cached == true && ($cached = $this->results->search($target)) !== false) {
			$this->results[$target] = $cached;

			return true;
		} else if ($target === false) {
			return true;
		}

		return false;
	}


	public function checkFileExists($target) {
		foreach(scandir(SKOLAR_TESTFILES_DIR) as $dir) {
			if(is_dir(SKOLAR_TESTFILES_DIR . DIRECTORY_SEPARATOR . $dir)) {
				foreach(scandir(SKOLAR_TESTFILES_DIR . DIRECTORY_SEPARATOR . $dir) as $file) {
					if($file == $target) {
						return array(
							"full_path" => join(DIRECTORY_SEPARATOR, array(SKOLAR_TESTFILES_DIR, $dir, $file)),
							"filename" => $file
						);
					}
				}
			}
		}

		return false;
	}

	public function getStorage() {
		return $this->results;
	}

	public function getCache() {
		return ($this->cache != false) ? $this->cache : null;
	}

	public function getFullUrl($part) {
		return $this->client->getBaseUrl() . "/" . $part;
	}
}

?>