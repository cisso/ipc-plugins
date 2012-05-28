<?php
	function module_system_format_time($time)
	{
		$time_split = array_filter(array(
			'weeks' => floor($time / 604800),
			'days' => floor($time / 86400) % 7,
			'hours' => floor($time / 3600) % 24,
			'minutes' => floor($time / 60) % 60,
			'seconds' => $time % 60,
		));
		$time_string = array();
		foreach($time_split as $label => $value)
			$time_string[] = "$value " . ($value === 1 ? substr($label, 0, -1) : $label);
		$time_string = implode(', ', $time_string);
		return $time_string;
	}
	
	$memory = $EMPTY;
	$avgTicks = $EMPTY;
	$ticks = $EMPTY;
	$events = $EMPTY;
	$runtime = $EMPTY;
	$ping = $EMPTY;
	if($this->connected)
	{
		$memory = number_format($this->send('system', 'memory')) . ' B';
		$avgTicks = number_format($this->send('system', 'ticks'), 3) . ' / sec';
		$ticks = implode(',', (array) $this->send('system', 'ticks', time() - 500));
		#$ticks = print_r($ticks, true);
		$runtime = module_system_format_time($this->send('system', 'runtime'));
		$ping = module_system_format_time($this->send('system', 'lastping'));
		$ping = $ping ? "$ping ago" : $EMPTY;
	}

	$actionLabel = 'Refresh';
	$actionName = 'system[refresh]'
?>

<?php ob_start(); ?>
	<script type="text/javascript">
		$().ready(function() {
			var delay = 5000;
			var trigger = function() {
				$('#module-system input.refresh:submit').trigger('click');
				setTimeout(trigger, delay);
			};
			//setTimeout(trigger, delay);
		});
	</script>
<?php $this->head[] = ob_get_clean(); ?>

<form id="module-system" class="module" method="post">
	
	<legend>System</legend>
	
	<div class="help">
		When pressing reload you might notice a slight, constant increase in memory usage.<br />
		This is due to values being cached statically. For implementation details take a look at SystemApi.php
	</div>
	
	<ul>

		<li>
			<span class="label">Runtime</span>
			<span class="value"><?php echo $runtime ?></span>
		</li>

		<li>
			<span class="label">Memory</span>
			<span class="value"><?php echo $memory ?></span>
		</li>

		<li>
			<span class="label">Tick rate</span>
			<span class="value"><?php echo $avgTicks ?></span>
			<!-- span id="ticks" class="value">Ticks: <?php echo $ticks ?></span -->
		</li>

		<li>
			<span class="label">Event rate</span>
			<span class="value"><?php echo $events ?></span>
		</li>

		<li>
			<span class="label">Last ping</span>
			<span class="value"><?php echo $ping ?></span>
		</li>

		<li>
			<span class="label">Action</span>
			<span class="value">
				<input type="submit" class="refresh" title="refresh" value="Refresh" name="system[refresh]" />
			</span>
		</li>

	</ul>	
</form>