<?php

namespace Skolar\Modules\Server;

class FindschoolModule extends \Skolar\Modules\BaseModule {

	public function parse($content = null) {

		$query = $this->getRequestParam("query");
		$limit = (is_numeric($this->getRequestParam("limit")) && $this->getRequestParam("limit") <= 60 && $this->getRequestParam("limit") >= 0) ? $this->getRequestParam("limit") : 60;


		if(strpos($query, " ") !== false) {
			$query = implode(" ", array_map(function($element) {
				return $element . "*";
			}, explode(" ", $query)));
		}


		$data = \dibi::query("SELECT *, 
				MATCH(name) AGAINST('%s",$query,"' IN BOOLEAN MODE) + MATCH(address) AGAINST ('~%s",$query,"' IN BOOLEAN MODE) as relevancy 
				FROM [schoollist] WHERE 
				MATCH(name) AGAINST('%s",$query,"' IN BOOLEAN MODE) OR MATCH(address) AGAINST ('~%s",$query,"' IN BOOLEAN MODE)
				ORDER BY relevancy DESC
				LIMIT 0, %i", $limit 
		)->fetchAll();

		return $this->response->setResult(array("findschool" => $data));
	}


	public function getParameters($request = null) {
		return $this->parameters;
	}
}
?>