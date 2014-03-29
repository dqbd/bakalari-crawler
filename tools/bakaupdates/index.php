<?php
	require __DIR__ . "/vendor/autoload.php";
	
	header("Content-type: text/plain");

	use \Guzzle\Http\Client;
	
	dibi::connect(array(
		"driver" => "mysqli",
		"user" => "root",
		"pass" => "ivanagroskova",
		"database" => "skolar"
	));
	
	$headers = 'From: bakaupdater@duong.cz' . "\r\n" .
           'Reply-To: david@duong.cz' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();
		   
	$versions = \dibi::query("SELECT * FROM webversions");
	
	foreach($versions as $version) {
		$client = new Client("http://bakalari.cz/");
		
		$response = $client->get("ftp".str_replace("/", "", $version["name"]).".inf")->send()->getBody(true);
		
		if(preg_match_all("/\*wb(.*?)\*/s", $response, $web) > 0) {
			$web = array_filter(array_map("trim", explode("\n", $web[1][0])));
			
			if(strtotime($web[2]) > strtotime($version['lastupdate'])) {
				mail("david@duong.cz", "Nová verze webové aplikace Bakaláři ".$version["name"].": ".$web[2], "Na Bakaláře byla nahraná nová verze webové aplikace ".$version["name"].". Je dobré se podívat na zpětnou kompabalitu. Datum: ".$web[2], $headers);
			}
			
			dibi::query("UPDATE [webversions] SET lastupdate = %s", date("Y-m-d H:i:s", strtotime($web[2])), " WHERE id = %i", $version["id"]);
		}
	}
?>