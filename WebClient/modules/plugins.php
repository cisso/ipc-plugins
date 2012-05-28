<?php
	$plugins = array();
	if($this->connected)
	{
		if(isset($enable))
			foreach($enable as $name => $val)
				$this->send('plugin', 'enable', array($name));

		if(isset($disable))
			foreach($disable as $name => $val)
				$this->send('plugin', 'disable', array($name));
		
		$plugins = (array) $this->send('plugin', 'list');
	}

?>
<form id="module-plugins" class="module" method="post">
	
	<legend>Plugins</legend>
	
	<div class="help">
		Individual plugins can be enabled or disabled.<br />
		<strong>Warning: Reenabling a plugin will currently cause the bot to crash.</strong>
	</div>
	
	<ul>
		<?php foreach($plugins as $name => $status): ?>
		<li>
			<span class="label"><?php echo $name ?></span>
			<span class="value">
				<?php $action = $status ? 'disable' : 'enable'; ?>
				<input type="submit" title="<?php echo $action ?>" class="<?php echo $action ?>" name="plugins[<?php echo $action ?>][<?php echo $name ?>]" value="<?php echo ucfirst($action) ?>" />
				<!-- input type="submit" class="config" name="plugins[config][<?php echo $name ?>]" value="Configure" / -->
			</span>
		</li>
		<?php endforeach; ?>
	</ul>	
</form>