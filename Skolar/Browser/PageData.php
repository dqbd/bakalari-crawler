<?php

namespace Skolar\Browser;

use \Symfony\Component\DomCrawler\Crawler;

class PageData implements \JsonSerializable {

	private $url = array("base"=> "", "begin" => "", "end" => "");
	private $code = 300;
	private $body = "";
	private $error = false;

	public function __construct($base_url, $url, $body, $code = 300, $final_url = "", $error = false) {
		$this->url["base"] = $base_url;
		$this->url["begin"] = $url;
		$this->url["end"] = $final_url;

		$this->fixUrls();

		$this->body = $body;
		$this->code = $code;

		$this->error = $error;
	}

	public function setBaseUrl($url) {
		return $this->setUrl("base", $url);
	}

	public function setBeginUrl($url) {
		return $this->setUrl("begin", $url);
	}

	public function setEndUrl($url) {
		return $this->setUrl("end", $url);
	}

	public function setUrl($type, $value) {
		$this->url[$type] = $value;
		$this->fixUrls();

		return $this;
	}

	public function getHttpCode() {
		return $this->code;
	}


	public function getUrls() {
		return $this->url;
	}

	public function getUrl($type, $absolute = false) {
		return ($absolute) ? $this->url["base"] . $this->url[$type] : $this->url[$type];
	}

	public function getRaw() {
		return $this->body;
	}
	
	public function getDom() {
		$crawler = new Crawler("", $this->getUrl("base"));
		$crawler->addHtmlContent($this->body);
		return $crawler;
	}

	public function isError() {
		return ($this->error !== false);
	}

	public function jsonSerialize() {
		return array(
			"urls" => $this->url,
			"http_code" => $this->code,
			"body" => $this->body,
			"error" => $this->error
		);
	}

	protected function fixUrls() {
		if(!empty($this->url["base"]) && strpos($this->url["begin"], $this->url["base"]) !== false) {
			$this->url["begin"] = str_replace($this->url["base"], "", $this->url["begin"]);
		}

		if(!empty($this->url["end"]) && strpos($this->url["end"], $this->url["base"]) !== false) {
			$this->url["end"] = str_replace($this->url["base"], "", $this->url["end"]);
		}
	}
}

?>