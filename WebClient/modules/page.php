<?php
	// Render modules first to give them a chance to add code to the page head
	$bot     = $this->renderModule('bot', array(), true);
	$system  = $this->renderModule('system', array(), true);
	$api     = $this->renderModule('api', array(), true);
	$plugins = $this->renderModule('plugins', array(), true);

	$example = isset($_GET['example']) ? $this->renderModule('example', array(), true) : false;

?><!doctype html>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
	<base href="<?php echo $this->baseUrl ?>" 
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

	<title>Phergie Web Interface</title>
	<link rel="stylesheet" href="css/reset.css">
	<link rel="stylesheet" href="css/style.css">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script src="js/script.js"></script>
	
	<?php echo implode("\n", $this->head); ?>

</head>
<body>
	
	<div id="header-container">
		<header class="wrapper">
			<h1 id="title">Phergie Bot Control Panel</h1>
		</header>
	</div>

	<div id="main" class="wrapper">
		
		<?php if($example) echo $example ?>

		<?php echo $bot ?>
		<?php echo $system ?>
		<?php echo $api ?>
		<?php echo $plugins ?>
		
		
	</div>
	<div id="footer-container">
		<footer class="wrapper">
		</footer>
	</div>
</body>
</html>
