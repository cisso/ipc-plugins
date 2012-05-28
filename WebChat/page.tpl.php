<?php if(!isset($bot)) return; ?><!doctype html>
<html lang="en">
<head>
	<base href="<?php echo $baseUrl ?>" 
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

	<title>Phergie Web Chat</title>
	<link rel="stylesheet" href="css/reset.css" />
	<link rel="stylesheet" href="css/style.css" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="js/script.js"></script>
	
</head>
<body>
	
	<div id="header-container">
		<header class="wrapper">
			<h1 id="title">Phergie Bot Web Chat</h1>
		</header>
	</div>

	<div id="wrapper">
		
		<ul id="tabs">
		</ul>
		
		<div id="output">
			<div id="channel-1" class="channel">
				<table>
				</table>
			</div>
		</div>
		
	</div>
	<div id="footer-container">
		<footer class="wrapper">
		</footer>
	</div>
</body>
</html>
