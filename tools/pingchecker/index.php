<!doctype html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Kontrola Pingu</title>
	<style>
		body {
			background: #ccc;
		}
		.item {
			background: #fff;
			padding: 14px;
			box-sizing: border-box;
			
			margin-bottom: 5px;
			
			overflow: hidden;
		}
		
		
		
		.item .a, .item .b {
			
			font-weight: bold;
			text-align: right;
			font-style: italic;
		}
		
		button {
			width: 100%;
			height: 50px;
			border: none;
			font-size: 32px;
			margin-bottom: 15px;
		}
	</style>
</head>
<body>
	<button class="begin">Začít!</button>
	<button class="sort">Seřadit!</button>
	
	<table>
	<tbody>
	<?php
		require_once __DIR__ . "/vendor/autoload.php";
		
		\dibi::connect(array(
			"driver" => "mysqli",
			"host" => "localhost",
			"username" => "root",
			"password" => "ivanagroskova",
			"database" => "skolar"
		));
		
		$data = \dibi::query("SELECT name, url FROM [schoollist] ORDER BY id ASC")->fetchAll();
		
		foreach($data as $item) {
	?>
	
	<tr class="item">
		<td class="name"><?php echo $item["name"]; ?></td>
		<td class="text"><?php echo $item["url"]; ?></td>
		<td class="a"></td>
		<td class="b"></td>
	</tr>
	
	<?php } ?>
	</tbody>
	</table>
	
	<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
	<script src="jquery.tinysort.min.js"></script>
	<script>
	$(document).ready(function() {
		$("button.begin").click(function() {
			check_item($('.item:first'));
		});
		
		function check_item(n) {
			$.get("version.php", {url: $('.text', n).text()}, function(d) {				
				
				$(".a", n).text(d["a"]);
				$(".b", n).text(d.b);
				
				if (n.next().length) {
					check_item(n.next());
				} else {
					$('.item').tsort("td.b");
				}
			});			
		}
	});
	</script>
</body>
</html>