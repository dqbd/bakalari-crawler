<?php
namespace Skolar\Modules\Server;

class TestfilesModule extends \Skolar\Modules\BaseModule {

	public function parse($content = null) {
		if($this->getRequestParam("system") == "bakalari") {
			$files = array_values(array_diff(scandir(SKOLAR_TESTFILES_DIR . "/bakalari"), array(".", "..")));
			return $this->getResponse()->setResult(array("testfiles" => $files));
		}
	}

}

?>