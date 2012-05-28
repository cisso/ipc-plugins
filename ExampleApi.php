<?php
class Phergie_Plugin_ExampleApi extends Phergie_Plugin_Abstract
{
	// We'll use this property to store client IDs and data for delayed responses.
	// For details on delayed responses see helloWorldDelayed().
	protected $delayedData = array();
	
	/**
	 * Register your plugin API.
	 *
	 * In this example we also add an API for the Command plugin.
	 */
	public function onLoad()
	{
		// Registering your API.
		// If you haven't implemented ipcCommands() you will also need to provide
		// a command map (see the next example).
		$this->getPluginHandler()->getPlugin('Ipc')->addApi($this);
		
		// Registering an API for a different plugin:
		// First we retrieve the instance of the plugin we want to add, in our case
		// the Command plugin.
		// Note that most plugins won't be suitable since their event handlers
		// require an event object and/or use the bot to send their response. In
		// those cases you're better off writing your own wrapper methods.
		$plugin = $this->getPluginHandler()->getPlugin('Command');
		// Next we define our command map, where we map the command "list" to the
		// plugin method "getMethods". This is optional for plugins that already
		// implement the method ipcCommands(), but even then it is handy to limit
		// the list of available commands or use different command names.
		$map = array('list' => 'getMethods');
		// Finally we get the Ipc plugin and tell it to add our new API by providing
		// the plugin instance, the command map and the API alias. If we had left
		// out the "cmd" alias, Ipc would use "Command" instead.
		$this->getPluginHandler()->getPlugin('Ipc')->addApi($plugin, $map, 'cmd');
		// Tip: You can use the optional alias to add a plugin multiple times using
		// different API aliases and command maps.
	}
	
	
	/**
	 * This method is completely optional. It allows you to provide a different
	 * name for your API. If you skip this method, the plugin name will be used.
	 * This method gets invoked only once, when the plugin is added as an API.
	 */
	public function ipcAlias()
	{
		// The alias may only contain upper- and lowercase letters, digits and the
		// following characters: "-", "_", ".", ":"
		return 'example';
	}
	
	/**
	 * Returns a list of available commands and the methods that should be called.
	 */
	public function ipcCommands()
	{
		return array(
			// This simple command takes an argument and returns a string.
			'hello'        => 'helloWorld',
			// This command demonstrates the use of delayed responses.
			'helloDelayed' => 'helloWorldDelayed',
		);
	}
	
	
	/**
	 * The callback for our "hello" command.
	 */
	public function helloWorld($name = 'world')
	{
		// No magic here. The return value will be serialized by Ipc and sent to the
		// client.
		return "Hello $name!";
	}
	
	
	/**
	 * The callback for our "helloDelayed" command, using delayed responses.
	 * Delayed responses are great if you have to wait or collect IRC data over
	 * multiple events before you can compile the return value.
	 * Common use cases for delayed responses are channel and user lists.
	 *
	 * When you tell Ipc to delay a response using delayResponse(), it will keep
	 * the connection to the client open and pass you a unique token that identi-
	 * fies that connection.
	 * When you have performed all your operations and want to return a value to
	 * the client, call Ipc's sendResponse() and pass the connection token and
	 * your data.
	 * Ipc will pass the data to the client and close the connection.
	 */
	public function helloWorldDelayed($delay, $name = 'world')
	{
		// Here we tell Ipc to keep the current client waiting and retrieve the
		// token that will enable us to respond later on.
		$responseId = $this->getPluginHandler()->getPlugin('Ipc')->delayResponse();
		// We store the current time and the arguments in a dataset identified by
		// the token.
		$this->delayedData[$responseId] = array(time(), $delay, $name);
		// Next stop: onTick()! :)
	}
	
	
	/**
	 * We'll use the tick event to check if we should respond to any of our
	 * delayed requests.
	 */
	public function onTick()
	{
		// Skip if there are now delayed responses
		if(empty($this->delayedData))
			return;
		
		foreach(array_keys($this->delayedData) as $id)
		{
			// Retrieve the time the command got invoked and the provided arguments
			list($time, $delay, $name) = $this->delayedData[$id];
			// We don't want anyone to accidently keep an connection open for hours.
			// Let's limit the delay to 5 seconds.
			$delayLimited = min(5, $delay);

			// Check if we should respond, otherwise skip
			if($time + $delayLimited > time())
				continue;
			
			// We have all the data we need in $delay and $name, so we're going to delete
			// the current entry.
			unset($this->delayedData[$id]);
			// Build the response
			$data = "Hello $name, thank you for waiting $delayLimited seconds for a response!";
			if($delayLimited < $delay)
				// No, it's not a bug. We limited the time on purpose :)
				$data .= "\nI know you wanted to wait $delay seconds, but I didn't feel like waiting *that* long.";
			// Finally, we tell Ipc to return our data. Note that $id contains the
			// connection token that identifies the client request.
			$this->getPluginHandler()->getPlugin('Ipc')->sendResponse($id, $data);
			// And that's it. Ipc closed the connection to the client.
		}
	}

}