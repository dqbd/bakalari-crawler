<?php

$headers = 'From: bakaupdater@duong.cz' . "\r\n" .
           'Reply-To: david@duong.cz' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();
		   
		   

mail("david@duong.cz", "Nová verze webové aplikace Bakaláři: "."3232"., "Na Bakaláře byla nahraná nová verze webové aplikace "."derp".". Je dobré se podívat na zpětnou kompabalitu. Datum: "."derp", $headers);

?>