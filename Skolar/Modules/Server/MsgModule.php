<?php
namespace Skolar\Modules\Server;

class MsgModule extends \Skolar\Modules\BaseModule {
	public function parse($content = null) {
		$query = \dibi::query("SELECT * FROM [messages] ORDER BY [timestamp] DESC LIMIT 1")->fetchAll();
		$result = array("msg" => "");

		if(count($query) == 1) {

			$result["msg"] = array(
				"contents" => $query[0]->content,
				"lastupdated" => $query[0]->timestamp->getTimestamp()
			);
		}

		return $this->getResponse()->setResult($result);
	}
}

?>