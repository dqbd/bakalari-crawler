<!doctype html>
<html>
	<head>
		<meta charset="UTF-8">
	</head>
	<body>
	<h2>POST:</h2>
	<pre style="word-wrap: break-word;"><?php parse_to_copy($_POST);  ?></pre>
	<h2>GET:</h2>
	<pre style="word-wrap: break-word;"><?php parse_to_copy($_GET);  ?></pre>
	</body>
</html>

<?php
	function parse_to_copy($arr) {
		echo "array(".PHP_EOL;
			foreach($arr as $key => $val) {
				echo "\t'".$key."' => '";
				print_r($val);
				echo "'";
				
				reset($arr);
				end($arr);
				if($key != key($arr)) {
					echo ",";
				}
				echo PHP_EOL;
			}
		echo ");".PHP_EOL;
	}
?>