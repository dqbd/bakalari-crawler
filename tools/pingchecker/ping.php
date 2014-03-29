<?php
	require_once __DIR__ . "/vendor/autoload.php";
	header("Content-type: text/plain");
	
	use \Guzzle\Http\Client;
	
	$client = new Client($_GET["url"]);
	
	$client->setDefaultOption('verify', false);
	$client->setDefaultOption('timeout', 60);
	
	try {
		$request = $client->get();
		
		$response = $request->send();
		
		/* echo $response->getInfo()["total_time"]. " s"; */
		
		echo json_encode(array(
			"a" => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],
			"b" => $response->getInfo()["total_time"]
		));
	} catch (Exception $e) {
		echo json_encode(array(
			"a" => "KO",
			"b" => "KO"
		));
	}
	
	
?>