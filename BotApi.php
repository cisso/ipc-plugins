<?php
class Phergie_Plugin_BotApi extends Phergie_Plugin_Abstract
{
	public function onLoad()
	{
		$this->getPluginHandler()->getPlugin('Ipc')->addApi($this);
	}
	
	public function ipcAlias()
	{
		return 'bot';
	}
	
	public function ipcCommands()
	{
		return array(
			'do' => 'sendCommand',
			'connection' => 'getConnectionData',
		);
	}
	
	public function getConnectionData()
	{
		$c = $this->getConnection();
		$data = array(
			'transport' => $c->getTransport(),
			'host'      => $c->getHost(),
			'port'      => $c->getPort(),
			'nick'      => $c->getNick(),
			'realname'  => $c->getRealname(),
			'username'  => $c->getUsername(),
		);
		return $data;
	}
	
	public function sendCommand()
	{
		$args = func_get_args();
		$command = 'do' . ucfirst(array_shift($args));
		return call_user_func_array(array($this, $command), $args);
	}
	
}