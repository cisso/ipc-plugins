<?php
/**
 * This is example shows you the basics of implementing your own module.
 * Don't forget to add your module to modules/page.php
 *
 * Available variables:
 *
 *   $this     WebClient instance
 *   $EMPTY    Text displayed for missing content, "---" by default.
 *
 *   Any indexes in $_POST['the_module_name'] will be extracted into variables.
 *
 *   To include the module, append "?example" to the URL.
 */

	$someMessage = '';
	
	// If $_POST['example']['clickMe'] is set, it will be available as $clickMe
	if(isset($clickMe))
	{
		$someMessage .= 'That&rsquo;s how it&rsquo;s done!';
	}

	// If $_POST['example']['textInput'] is set, it will be available as $textInput
	elseif(isset($textInput))
	{
		$someMessage .= 'Your input was:<br />';
		$someMessage .= '<pre>' . htmlspecialchars($textInput['value']).'</pre>';
		$someMessage .= '<br />';
		
		// Check if the user hit Return to submit the text. This will only work
		// if the user has Javascript enabled. Otherwise the browser will always
		// use the the first submit button in a form, in our case 'clickMe'
		if(!isset($textInput['send']))
			$someMessage .= '<br />You submitted by hitting Return.';
		else
			$someMessage .= '<br />You submitted by clicking &ldquo;Send&rdquo;.';
	}
	
	// Set the default text for empty values
	if(!strlen($someMessage))
		$someMessage = $EMPTY;
	
	/**
	 * Hint: If you like all your Submit actions and data combined in a single
	 * variable, name your input elements like this:
	 *   example[command][clickMe]
	 *   example[command][textInput][value]
	 *   example[command][textInput][send]
	 *
	 * This will provide you with $command['clickMe'] and $command['textInput'].
	 */
?>

<form id="module-example" class="module" method="post">
	
	<legend>Example</legend>
	
	<!-- The help block is optional. -->
	<div class="help">
		This is a simple module meant to guide you in the creation of your own module.
	</div>
	
	<ul>
		<li>
			<span class="label">Buttons</span>
			<span class="value">
				This is a button that will invoke an action:
				<input type="submit" name="example[clickMe]" value="Click me!" />
			</span>
			
		</li>
		
		<li>
			<span class="label">Input</span>
			<span class="value">
				You can also enter text. Try submitting by hitting Return or by clicking the <strong>Send</strong> button:<br />
				<br />
				<input type="text" value="" name="example[textInput][value]" size="40" />
				<input type="submit" name="example[textInput][send]" value="Send" />
			</span>
		</li>
		
		<li>
			<span class="label">Messages</span>
			<span class="value"><?php echo $someMessage ?></span>
		</li>
	</ul>
</form>