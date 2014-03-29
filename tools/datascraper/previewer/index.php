<?php

	header("Content-type: application/json");
	require '../../../lib/dibi.min.php';
	
	dibi::connect(array( 
		"host" => "localhost",
		"user" => "root",
		"pass" => "ivanagroskova",
		"database" => "skolar"
	));
	
	$derp = dibi::query("SELECT coords FROM [schoollist]");
	
	$array = array();
	
	foreach($derp as $item) {
		$array[] = $item["coords"];
	}
	
	echo "data = ".json_encode($array);
?>