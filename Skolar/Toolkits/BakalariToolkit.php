<?php

namespace Skolar\Toolkits;

class BakalariToolkit {

	public static function assignUrl($name, $dataset) {
		if(!is_array(($dataset))) {
			return "";
		}

		return array_flip(array_map("strtolower", $dataset))[strtolower($name)];
	}

	public static function fillParameters($dom, $required, $optional) {
		$data = ($dom instanceof \Skolar\Browser\PageData) ? $dom->getDom() : $dom;

		$form = $data->filterXPath("//form")->form();

		foreach($optional as $key => $value) {
			try {
				$form->setValues(array($key => $value));
			} catch (\Exception $e) {}
		}

		$arguments = $form->getPhpValues();

		foreach ($data->filterXPath("//*[self::input and @value and @name and (@type='button' or @type='submit' or @type='image')]") as $button) {
		    $arguments[$button->getAttribute("name")] = $button->getAttribute("value");
		}

		return array_merge($arguments, $required);
	}

	public static function checkIfPermanentLogin($cache) {
		
	}

}

?>