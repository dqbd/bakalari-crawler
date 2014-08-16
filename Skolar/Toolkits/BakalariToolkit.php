<?php

namespace Skolar\Toolkits;

class BakalariToolkit {

	public static function assignUrl($name, $dataset) {
		if(!is_array(($dataset))) {
			return "";
		}

		return array_flip(array_map("strtolower", $dataset))[strtolower($name)];
	}

	public static function getFormParams($context, $params = array()) {
		if(count($params) == count($params, COUNT_RECURSIVE)) {
			$params = array(
				"optional" => $params,
				"required" => array()
			);
		}

		$wave = isset($context["pass"]) ? $context["pass"] : 0;

		if($wave != 0 || 
			!isset($context["pagedata"]) || 
			!($context["pagedata"] instanceof \Skolar\Browser\PageData)) {

			return array();
		}

		return self::fillParameters($context["pagedata"]->getDom(), $params["required"], $params["optional"]);
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

	public static function getDate($input) {
		date_default_timezone_set("Europe/Prague");

		if(strtotime(str_replace(".", "-", $input)) !== false) {
			return strtotime($input);
		}

		if(date("n") >= 9) {
			$schoolyear = array("begin" => (int) date("Y"), "end" => (int)date("Y") + 1);
		} else {
			$schoolyear = array("begin" => (int) date("Y") - 1, "end" => (int)date("Y"));
		}

		preg_match("/([0-3]?[0-9])\.\s*([0-1]?[0-9])[\.]?/", $input, $parsed);
		list($input, $day, $month) = $parsed; 
		$year = ($month >= 9) ? $schoolyear["begin"] : $schoolyear["end"];

		return strtotime(join("-", array($day, $month, $year)));
	}
}

?>