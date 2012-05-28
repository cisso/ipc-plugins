<?php
	$status     = 'Disconnected';
	$connection = $EMPTY;
	$api        = $this->address;
	$nick       = $EMPTY;

	$actionLabel = 'Connect';
	$actionName  = 'bot[connect]';
	$actionClass = 'enable';
	
	if(!$this->connected && isset($detach))
	{
		foreach(ob_list_handlers() as $handler)
			ob_end_clean();
			
		header("Connection: close");
		ignore_user_abort();
		ob_start();
		
		// Insert custom output here
		
		header('Content-Length: ' . ob_get_length());
		ob_end_flush();
		flush();
		
		$bot = new Phergie_Bot;
		$bot->run();

		exit;
	}

	if(!$this->connected && isset($connect))
	{
		$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		
		$data = array();
		$data['bot']['detach'] = true;
		
		$opts = array();
		$opts['http'] = array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => http_build_query($data)
		);
		$context = stream_context_create($opts);
		$stream = fopen($url, 'r', false, $context);
		stream_set_blocking($stream, 0);
		$result = stream_get_contents($stream);
		fclose($stream);
		ob_end_clean();
		header('Location: ' . $url);
		
		exit();
	}


	if($this->connected && isset($disconnect))
	{
		$this->send('bot', 'do', array('quit'));
		$this->connected = false;
	}

	if($this->connected)
	{
		$status         = 'Running';
		$connectionData = $this->send('bot', 'connection');
		$connection     = strtr('transport://host:port', $connectionData);
		$nick           = strtr('nick (username, realname)', $connectionData);
		$actionLabel    = 'Disconnect';
		$actionName     = 'bot[disconnect]';
		$actionClass    = 'disable';
	}

?>
<form id="module-bot" class="module" method="post">
	<legend>Bot</legend>
	
	<ul>
		<li>
			<span class="label">Status</span>
			<span class="value">
				<?php echo $status ?>
				<input type="submit" class="no-ajax <?php echo $actionClass ?>" value="<?php echo $actionLabel ?>" name="<?php echo $actionName ?>" />
			</span>
		</li>
		<li>
			<span class="label">Connection</span>
			<span class="value"><?php echo $connection ?></span>
		</li>
		<li>
			<span class="label">Nick</span>
			<span class="value"><?php echo "$nick" ?></span>
		</li>
		<li>
			<span class="label">API address</span>
			<span class="value"><?php echo $api ?></span>
		</li>
	</ul>	
</form>