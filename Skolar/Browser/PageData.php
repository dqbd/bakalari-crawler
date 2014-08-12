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

		if(strpos($url, $base_url) !== false) {
			$url = str_replace($base_url, "", $url);
		}

		if(strpos($final_url, $base_url) !== false) {
			$final_url = str_replace($base_url, "", $final_url);
		}


		$this->url["begin"] = $url;
		$this->url["end"] = $final_url;

		$this->body = $body;
		$this->code = $code;

		$this->error = $error;
	}

	public function getHttpCode() {
		return $this->code;
	}

	public function getRaw() {
		return $this->body;
	}

	public function getUrls() {
		return $this->url;
	}

	public function getUrl($method, $absolute = false) {
		return ($absolute) ? $this->url["base_url"] . $this->url[$method] : $this->url[$method];
	}

	public function getDom() {
		$crawler = new Crawler("", $this->base_url);
		return $crawler->addHttpContent($this->body);
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
}

?>