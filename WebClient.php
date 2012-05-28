<?php
class Phergie_Plugin_WebClient extends Phergie_Plugin_Abstract
{
	public function onLoad()
	{
		$address = $this->getConfig('webclient.address', '127.0.0.1:7654');
		$this->getConfig()->offsetSet('ipc.address', $address);
		$this->getPluginHandler()
			->getPlugins(array(
				'IpcApi',
				'PluginApi',
				'SystemApi',
				'BotApi',
				'ChannelApi',
				'UserInfo',
			));
	}
	
}