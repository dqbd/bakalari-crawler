<?php
namespace Skolar\Modules\Bakalari;

class BatchModule extends \Skolar\Modules\BaseModule {

	

	public function postParse($content) {
		$modules_list = explode(",", $content->get("requests"));

		$modules = array();

		foreach($modules_list as $module_name) {
			$requestparams = array("action" => $module_name);

			foreach($content as $key => $value) {
				$prefix = sprintf("requestparam-%s-", $module_name);
				if((substr($key, 0, strlen($prefix)) === $prefix)) {
					$key = str_replace($prefix, "", $key);

					$requestparams[$key] = $value;
				}
			}

			$module_namespace = sprintf("%s\\%sModule", __NAMESPACE__, ucfirst($module_name));
			
			if(!class_exists($module_namespace)) {
				throw new \Exception("Invalid parameters");
			}

			$modules[] = new $module_namespace($requestparams);
		}
		
		return $modules;
	}

	public function parse($content = null) { return null; }


}

?>