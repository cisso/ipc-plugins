<?php
class Phergie_Plugin_IpcApi extends Phergie_Plugin_Abstract
{
	public function onLoad()
	{
		$this->getIpc()->addApi($this);
	}
	
	public function ipcAlias()
	{
		return 'api';
	}
	
	public function ipcCommands()
	{
		return array(
			'discover' => 'getApiCommands',
			'reload'   => 'reloadApi',
			'add'      => 'addApi',
			'remove'   => 'removeApi',
			'hello'    => 'sayHello'
		);
	}
	
	public function getApiCommands($alias = null)
	{
		return $this->getIpc()->getApiCommands($alias);
	}
	
	public function reloadApi($alias)
	{
		return $this->getIpc()->reloadApi($alias);
	}
	
	public function addApi($name, $autoload = false)
	{
		if(!$autoload && $this->getPluginHandler()->hasPlugin($name))
			return false;
		$plugin = $this->getPluginHandler()->getPlugin($name);
		return $this->getIpc()->addApi($plugin);
	}
	
	public function removeApi($alias)
	{
		return $this->getIpc()->removeApi($alias);
	}
	
	public function sayHello()
	{
		return 'Hello!';
	}
	
	protected function getIpc()
	{
		return $this->getPluginHandler()->getPlugin('Ipc');
	}
}