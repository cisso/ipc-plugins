<?php
	$apis = array();
	$commandString = '';
	if($this->connected)
	{
		if(isset($reload))
		{
			$response = array();
			foreach($reload as $api => $val)
			{
				$classes[$api] = $this->send('api', 'reload', array($api));
				$response[] = "API <strong>$api</strong> reloaded. New class name: " . $classes[$api];
			}
			$response = implode("\n", $response);
		}
		elseif(isset($command))
		{
			$commandString = stripslashes($command['value']);
			$response = htmlspecialchars(print_r($this->sendRaw($commandString), true));
			$commandString = htmlspecialchars($commandString);
		}
		$classes = array();

		$apis = (array) $this->send('api', 'discover');
		ksort($apis);
	}

?>
<form id="module-api" class="module" method="post">
	
	<legend>API</legend>
	
	<div class="help">
		View available APIs and invoke commands directly. Examples:<br />
<pre>
  api discover
  system global server
  bot do privmsg NickServ "help info"
</pre>
	<br />
	Look at the plugins for individual arguments.<br />
	<strong>Warning:</strong> Use with care. While exceptions will be caught it is still possible to cause fatal errors that will crash the bot.
	</div>
	
	<ul>
	
		<?php foreach($apis as $name => $commands): ?>
		<li>
			<span class="label"><?php echo $name, (isset($classes[$name]) ? ' (' . $classes[$name] . ')' : '') ?></span>
			<span class="value"><?php echo implode(', ', (array) $commands) ?></span>
			<span class="value">
				<input type="submit" class="reload" value="Reload" title="reload plugin" name="api[reload][<?php echo $name ?>]" />
			</span>
		</li>
		<?php endforeach; ?>
		
		<li>
			<span class="label"> </span>
			<span class="value">
				<input type="text" value="<?php echo $commandString ?>" name="api[command][value]" size="40" />
			</span>
			<span class="value">
				<input type="submit" name="api[command][send]" value="Send" />
			</span>
		</li>
	
		<?php if(isset($response)): ?>
		<li>
			<span class="label">Response</span>
			<span class="value">
				<pre class="output"><?php echo $response ?></pre>
			</span>
		</li>
		<?php endif; ?>
		
	</ul>	
</form>