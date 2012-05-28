<?php
/**
 * TODO:
 * - Support for multiple server connections, each having its own interface
 */

class Phergie_Plugin_Ipc extends Phergie_Plugin_Abstract
{
	const API_NOT_AVAILABLE = 'API is not available';

	const API_FUNCTION_NOT_AVAILABLE = 'API command is not available';

	const API_FUNCTION_EXCEPTION = 'Error while invoking API command';

	const API_RELOAD_ERROR = 'Error while reloading API';
	
	const STREAM_CHUNK_SIZE = 4096;
	
	protected $apis = array();
		
	protected $address;
	
	protected $socket;
	
	protected $currentClient;
	
	protected $delayResponse = false;

	protected $delayedClients = array();
	

	public function onLoad()
	{
		$this->address = $this->getConfig('ipc.address', '127.0.0.1:8765');
		$this->socket = $this->startServer($this->address);
	}

	
	/**
	 * Closes any remaining connections.
	 *
	 */
	public function __destruct()
	{
		if(is_resource($this->socket))
			$this->stopServer($this->socket);
	}

	
	/**
	 * Register a plugin API.
	 * 
	 * The recommended way to pass a list of commands is to add a method ipcCommands() that returns
	 * a $commandMap array. Use the $commandMap argument only for plugins that do not implement
	 * this method.
	 *
	 * @param $plugin object     The plugin instance; has to be an instance of Phergie_Plugin_Abstract
	 * @param $commandMap array  An array of available commands in the format: command => method
	 * @param $alias string      The API alias; by default ipcAlias() or the plugin name will be used
	 *
	 * @return bool
	 */
	public function addApi($plugin, array $commandMap = null, $alias = null)
	{
		if(!$plugin instanceof Phergie_Plugin_Abstract)
			return false;

		$name = $plugin->getName();
		
		if(!is_string($alias) || !strlen($alias))
			$alias = method_exists($plugin, 'ipcAlias') ? $plugin->ipcAlias() : $name;
			
		// Checking the alias format
		If(!preg_match('/^[A-Za-z0-9_\-.:]+$/', $alias))
			return false;

		if(isset($this->apis[$alias]))
			return $this->apis[$alias]['name'] === $name;
				
		$this->apis[$alias] = array(
			'name'     => $name,
			'class'    => get_class($plugin),
			'commands'  => $commandMap
		);
		return true;
	}
	
	public function removeApi($alias, $disablePlugin = false)
	{
		if(!isset($this->apis[$alias]))
			return false;
		$name = $this->apis[$alias]['name'];
		unset($this->apis[$alias]);
		if($disablePlugin && $this->getPluginHandler()->hasPlugin($name))
			$this->getPluginHandler()->removePlugin($name);
		return true;
	}
	
	/**
	 * Get a list of available APIs and methods.
	 *
	 * @param $name string  The API name; if empty, all APIs will be returned
	 *
	 * @return array  Multiple arrays of method names, indexed by API name
	 */
	public function getApiCommands($aliases = null)
	{
		$aliases = empty($aliases) ? array_keys($this->apis) : (array) $aliases;
		$commands = array();
		foreach($aliases as $alias)
		{
			if(false === $commandMap = $this->getCommandMap($alias))
				continue;
			$commands[$alias] = array_keys($commandMap);
		}
		return $commands;
	}
	
	
	protected function getCommandMap($alias, $autoRemove = true)
	{
		if(!isset($this->apis[$alias]))
			return false;
		$api = $this->apis[$alias];
		$plugin = $this->getPluginHandler()->getPlugin($api['name']);
		if(!$plugin && $autoRemove)
		{
			unset($this->apis[$alias]);
			return false;
		}
		
		$commandMap = array();
		if(is_array($api['commands']))
			$commandMap = (array) $api['commands'];
		elseif(method_exists($plugin, 'ipcCommands'))
			$commandMap = (array) $plugin->ipcCommands();
		return $commandMap;
	}
	
	
	/**
	 * 
	 */
	protected function startServer($address)
	{
		if(!$socket = stream_socket_server("tcp://$address", $errno, $errstr))
			$this->fail("Failed to start server: $errstr");
		stream_set_blocking($socket, 0);
		
		return $socket;
	}
	
	
	protected function stopServer($socket)
	{
		return stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
	}

	
	/**
	 * Listens for clients and executes commands.
	 * Commands are expected in the format: array( $api, $command, array( $arg1, $arg2, ... ) )
	 *
	 * @param resource $socket
	 */
	protected function listen($socket)
	{
		$readSockets = array($socket);
		$writeSockets = array();
		
		if(stream_select($readSockets, $writeSockets, $writeSockets, 0))
		{
			$remoteSocket = stream_socket_accept($socket, 0);
			$data = fread($remoteSocket, self::STREAM_CHUNK_SIZE);
			if($data)
				if(($data = @unserialize($data)) && is_array($data))
				{
					$this->delayResponse = false;
					list($alias, $command, $args) = $data;
					$this->currentClient = $remoteSocket;
					$result = $this->callApi($alias, $command, (array) $args);
					if(!$this->delayResponse)
						$this->writeData($remoteSocket, $result);
					$this->currentClient = null;
				}
		}
	}
	
	protected function writeData($socket, $data)
	{
		$chunks = str_split(serialize($data), self::STREAM_CHUNK_SIZE);
		$chunks[] = PHP_EOL;
		$result = array();
		$success = true;
		foreach($chunks as $chunk)
		{
			$result = fwrite($socket, $chunk);
			$success = $success && $result !== false && $result !== 0;
		}
		return $success;
	}
	
	
	protected function callApi($alias, $command, array $args)
	{
		if(false === $commandMap = $this->getCommandMap($alias))
			return self::API_NOT_AVAILABLE;
		$plugin = $this->getPluginHandler()->getPlugin($this->apis[$alias]['name']);
		if(!isset($commandMap[$command]) || !method_exists($plugin, $commandMap[$command]))
			return self::API_FUNCTION_NOT_AVAILABLE;
		try
		{
			return call_user_func_array(array($plugin, $commandMap[$command]), (array) $args);
		}
		catch(Exception $e)
		{
			return self::API_FUNCTION_EXCEPTION . ': ' . $e->getMessage();
		}
	}

	
	public function delayResponse($timeout = null)
	{
		if(!$this->currentClient)
			return false;
		$id = uniqid('response-id-');
		$this->delayedClients[$id] = array(
			'time'    => time(),
			'socket'  => $this->currentClient,
			'timeout' => $timeout
		);
		$this->delayResponse = true;
		return $id;
	}

	
	public function sendResponse($id, $data)
	{
		if(!isset($this->delayedClients[$id]))
			return false;
		
		$socket = $this->delayedClients[$id]['socket'];
		unset($this->delayedClients[$id]);
		
		return $this->writeData($socket, $data);
	}
	
	
	/**
	 *
	 * @return string The class name of the new API instance
	 */
	public function reloadApi($alias)
	{
		if(!isset($this->apis[$alias]))
			return self::API_NOT_AVAILABLE;
		
		$class = $this->apis[$alias]['class'];
		$newClass = uniqid($class . '__');
		$pattern = '/(class\s+)(' . $class . ')(?=\W)/';

		$pluginHandler = $this->getPluginHandler();
		$name = $this->apis[$alias]['name'];
		$info = $pluginHandler->getPluginInfo($name);
		$code = file_get_contents($info['file']);
		$code = preg_replace($pattern, '$1' . $newClass . '$3', $code, 1);
		if(false === @eval('?>' . $code))
			return self::API_RELOAD_ERROR;
		try
		{
			$instance = $pluginHandler->getPlugin($name);
			$newInstance = new $newClass;
			$newInstance->setName($name);
			// If class implements ipcReload(), call it and pass the old plugin instance
			if(method_exists($newClass, 'ipcReload'))
				$newInstance->ipcReload($instance);
			$pluginHandler->removePlugin($name);
			$pluginHandler->addPlugin($newInstance);
		}
		catch(Exception $e)
		{
			return self::API_RELOAD_ERROR;
		}
		return get_class($pluginHandler->getPlugin($name));
	}

	
	public function onTick()
	{
		if(is_resource($this->socket))
			$this->listen($this->socket);
	}
}