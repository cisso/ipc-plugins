<?php
class Phergie_Plugin_PluginApi extends Phergie_Plugin_Abstract
{
	public function onLoad()
	{
		$this->getPluginHandler()->getPlugin('Ipc')->addApi($this);
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
		$plugins = array();
		$pluginHandler = $this->getPluginHandler();
		foreach(glob(realpath(dirname(__FILE__)) . '/*.php') as $file)
		{
			$file = strtolower(basename($file, '.php'));
			if(!isset($blacklist[$file]))
				$plugins[$file] = $pluginHandler->hasPlugin($file);
		}
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