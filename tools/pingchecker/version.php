<?php
	require_once __DIR__ . "/vendor/autoload.php";
	
	$output = false;
	
	function shutdown() {
		global $output;
		if (!$output) {
			echo json_encode(array(
				"a" => "KO",
				"b" => "KO"
			));
		}
	}
	
	error_reporting(0);
		
	register_shutdown_function('shutdown');

	header("Content-type: application/json");
	
	use \Goutte\Client;
	
	$client = new Client();
	
	$derp = (new \Guzzle\Http\Client())->setDefaultOption('verify', false)->setDefaultOption('timeout', 29);
	
	$client->setClient($derp);
	
	try {
		$crawler = $client->request("GET", $_GET["url"]);
		
		$a = $crawler->filterXPath("//*[@class='lbver']")->text();
		
		echo json_encode(array(
			"a" => $a,
			"b" => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]
		));
		
		$output = true;
	} catch (Exception $e) {
		echo json_encode(array(
			"a" => "KO",
			"b" => "KO"
		));
		$output = true;
	}

	
	
?>