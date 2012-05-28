<?php

class Phergie_Plugin_PluginApi extends Phergie_Plugin_Abstract
{
	protected $phpSubstClass = false;
	
	public function onLoad()
	{
		$this->getPluginHandler()->getPlugin('Ipc')->addApi($this);

		// Extend Phergie_Plugin_Handler and add static getter for $paths.
		// Create dynamic unique class name to support Ipc reload
		$this->phpSubstClass = uniqid('PluginApi_PhSubst__');
		$code = 'class ' . $this->phpSubstClass . ' extends Phergie_Plugin_Handler'
			.'{static function getPaths(Phergie_Plugin_Handler $obj){return $obj->paths;}}';
		eval($code);
	}
	
	public function ipcAlias()
	{
		return 'plugin';
	}
	
	public function ipcCommands()
	{
		return array(
			'list'    => 'getPluginList',
			'info'    => 'getPluginInfo',
			'enable'  => 'enablePlugin',
			'disable' => 'disablePlugin',
		);
	}
	
	public function getPluginList()
	{
		$blacklist = array_flip(array('abstract', 'handler'));
		$pluginHandler = $this->getPluginHandler();
		if($this->phpSubstClass)
			$paths = call_user_func($this->phpSubstClass . '::getPaths', $pluginHandler);
		else
			// Fallback that will only discover plugins in the file's directory
			$paths = array('path' => dirname(__FILE__), 'prefix' => 'Phergie_Plugin_');
		
		$plugins = array();
		foreach($paths as $path)
			foreach(glob(realpath($path['path']) . '/*.php') as $file)
			{
				$file = strtolower(basename($file, '.php'));
				if(!isset($blacklist[$file]))
					$plugins[$file] = $pluginHandler->hasPlugin($file);
			}
		ksort($plugins);
			
		return $plugins;
	}
	
	public function enablePlugin($name)
	{
		return (bool) $this->getPluginHandler()->addPlugin($name);
	}
	
	public function disablePlugin($name)
	{
		return (bool) $this->getPluginHandler()->removePlugin($name);
	}
	
	public function getPluginInfo($name)
	{
		$info = $this->getPluginHandler()->getPluginInfo($name);
		$info['enabled'] = $this->getPluginHandler()->hasPlugin($name);
		return $info;
	}
}
