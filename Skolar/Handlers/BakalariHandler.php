<?php
namespace Skolar\Handlers;

use \Symfony\Component\DomCrawler\Crawler;
use \Skolar\Toolkits\BakalariToolkit;

class BakalariHandler implements \Skolar\Handlers\BaseHandler {

	protected $module = null;
	protected $params = null;

	private $browser = null;

	private $baseflow = array("login", "navigace");

	protected $dataflow = array();
	protected $max_passes = 2;

	public function __construct(\Skolar\Modules\BaseModule $module, $parameters) {
		$this->module = $module;
		$this->params = $parameters;
	}

	public function output() {

		$this->initBrowser();

		$module_requests = $input = ($this->module->getName() == "batch") ? $this->module->postParse($this->params) : $this->module;

		//detect dupes in $module_request
		if(is_array($module_requests)) {
			$module_requests = array_filter($module_requests, function($module) {
				return !(in_array((string)$module, $this->baseflow));
			});
		} else  {
			$input = array($module_requests);

			if (!in_array($module_requests, $this->baseflow)) {
				$module_requests = array($module_requests);
			} else {
				$module_requests = array();
			}
		}

		foreach($this->baseflow as $module_name) {
			$this->baseflow[$module_name] = \Skolar\Dispatcher::createModule($module_name, "bakalari", $this->params);
		} 

		$results = array();

		/*
		* 1. Najeď na uvod.aspx a ověř, zda máme k dispozici navigaci (nepříhlašený nemá navigace), pokud ANO -> PŘESKOČ NA 5
		* 2. Pokud NE -> vytáhni login.aspx (vytáhne cache když byl prohlížeč přesměrován z uvod.aspx do login.aspx) a získaň parametry
		* 3. Proveď přihlášení a ověř, zda jsi tentokrát přihláš + máme k dispozici tu navigaci, pokud ANO -> PŘESKOČ NA 5
		* 4. Pokud NE -> vypiš rovnou a KONEC
		* 5. Vlož do dataflow navigaci a i do bufferu pro výsledky (results) -> KONEC
		*/
		$data = $this->browser->loadSingle($this->baseflow["navigace"]->getUrl());

		if($this->baseflow["navigace"]->preParse($data) === false) {
			$login_data = $this->browser->loadSingle($this->baseflow["login"]->getUrl(), array(), true);

			$this->baseflow["login"]->defineParameters(array("pagedata" => $login_data));

			$data = $this->browser->loadSingle(
				$this->baseflow["login"]->getUrl(), 
				$this->baseflow["login"]->getFormParams()
			);

			//check if logged in now
			$login_status = $this->baseflow["login"]->parse($data);

			if( $this->baseflow["login"]->postParse($login_status) === false || 
				$this->baseflow["navigace"]->preParse($data) === false) {
				return $login_status;
			} else {
				$results["login"] = $login_status;
			}
		} else {
			$results["login"] = $this->baseflow["login"]->parse($data);
		}


		$this->dataflow["navigace"] = $this->baseflow["navigace"]->postParse($data);
	
		//jedna výjimka, jelikož je děsně neefektivní načítat všechny moduly, když nakonec to vyfiltrujeme později.
		if(in_array("navigace", $input)) {
			$results["navigace"] = $this->baseflow["navigace"]->parse($data);
		}

		$urls = array();
		$params = array();

		foreach($module_requests as $key => $module) {
			$module->defineParameters($this->dataflow);        

			if($module->getUrl() === false) {
				$results[] = (new \Skolar\Response())->setError("No existing URL");
				unset($module_requests[$key]);
				continue;
			}

			$urls[] = $module->getUrl();
		}

		/*
		* 1. Proveď požadavek
		* 2. Získej ekvivalentní kus, který modul by chtěl
		* 3. Zjisti pokud můžu už vytáhnout data ze stránky pomocí funkce preParse, pokud ano -> KONEC
		* 4. Pokud ne a máme ještě příležitost stáhnout další data -> vlož parametry pro další vlnu
		* 5. Pokud už je konec, vypiš chybný požadavek
		*/
		for($this->dataflow["pass"] = 0; $this->dataflow["pass"] < $this->max_passes; $this->dataflow["pass"]++) {
			$downloaded = $this->browser->load($urls, $params);

			foreach($module_requests as $key => $module) {
				$result = $downloaded->get($module->getUrl());

				$tempflow = array_merge(array("pagedata" => $result), $this->dataflow);
				$module->defineParameters($tempflow);

				if($module->preParse($result)) {
					$parsed = $module->parse($result);

					if(($postparsed = $module->postParse($parsed)) !== $parsed) {
						$this->dataflow[$module->getName()] = $postparsed; 
					}

					$results[$module->getName()] = $parsed;

					//unset all other things
					unset($module_requests[$key]);
					if(isset($params[$module->getUrl()])) {
						unset($params[$module->getUrl()]);
					}

					if(($url_key = array_search($module->getUrl(), $urls)) !== false) {
					    unset($urls[$url_key]);
					}
				} else if (!empty($module->getFormParams()) && $this->dataflow["pass"]+1 < $this->max_passes) {
					$params[$module->getUrl()] = $module->getFormParams();
				} else {
					$results[$module->getName()] = (new \Skolar\Response())->setError("Parser failed to parse");
				}
			}
		}

		//Filtrujeme to, co nechceme
		foreach($results as $key => $result) {
			if(!in_array($key, $input)) {
				unset($results[$key]);
			}
		}

		return $results;
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

		$this->browser = new \Skolar\Browser($url, $cachepath);
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