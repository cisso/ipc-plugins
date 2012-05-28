<?php
class Phergie_Plugin_ChannelApi extends Phergie_Plugin_Abstract
{
	protected $delayedData = array(
		'names'    => array(),
		'channels' => array(),
		'joined'   => array(),
	);

	
	public function onLoad()
	{
		$this->getPluginHandler()->getPlugin('Ipc')->addApi($this);
	}

	
	public function ipcReload($instance)
	{
		$this->logStatus  = $instance->logStatus;
		$this->logContent = $instance->logContent;
	}

	
	public function ipcAlias()
	{
		return 'chan';
	}

	
	public function ipcCommands()
	{
		return array(
			'names'    => 'apiNames',
			'channels' => 'apiChannels',
			'joined'   => 'apiJoined',
		);
	}


	public function onResponse()
	{
		$this->handleNames();
		$this->handleChannels();
		$this->handleJoined();
	}
	
		
	public function apiNames($channels = null)
	{
		$id = $this->getPluginHandler()->getPlugin('Ipc')->delayResponse();
		$this->delayedData['names'][$id] = array();
		$this->doNames($channels);
	}


	public function apiChannels($channels = null)
	{
		$id = $this->getPluginHandler()->getPlugin('Ipc')->delayResponse();
		$this->delayedData['channels'][$id] = array();
		$this->doList($channels);
	}
	
	
	public function apiJoined()
	{
		$id = $this->getPluginHandler()->getPlugin('Ipc')->delayResponse();
		$this->delayedData['joined'][$id] = array();
		$this->doWhois($this->getConnection()->getNick());
	}
	

	protected function handleNames()
	{
		if(empty($this->delayedData['names']))
			return;
		
		$id = key($this->delayedData['names']);
		$code = (int) $this->getEvent()->getCode();

		if($code === 353)
		{
			$names = explode(' ', trim($this->getEvent()->getDescription()));
			$channel = array_shift($names);
			foreach($names as $name)
				$this->delayedData['names'][$id][$channel][] = $name;
		}
		elseif($code === 366)
		{
			$this->getPluginHandler()->getPlugin('Ipc')->sendResponse($id, $this->delayedData['names'][$id]);
			unset($this->delayedData['names'][$id]);
		}
	}


	protected function handleChannels()
	{
		if(empty($this->delayedData['channels']))
			return;
		
		$id = key($this->delayedData['channels']);
		$code = (int) $this->getEvent()->getCode();

		if($code === 322)
		{
			list($channel, $count, $topic) = explode(' ', trim($this->getEvent()->getDescription()), 3);
			$topic = substr($topic, 1);
			$this->delayedData['channels'][$id]['#' . $channel] = array(
				'count' => $count,
				'topic' => $topic
			);
		}
		elseif($code === 323)
		{
			$this->getPluginHandler()->getPlugin('Ipc')->sendResponse($id, $this->delayedData['channels'][$id]);
			unset($this->delayedData['channels'][$id]);
		}
	}
	
	
	protected function handleJoined()
	{
		if(empty($this->delayedData['joined']))
			return;

		$id = key($this->delayedData['joined']);
		$code = (int) $this->getEvent()->getCode();
		
		if($code === 319)
		{
			// We need to use getRawData(), since getDescription() cuts off the first character
			list( , , $nick, $channels) = explode(' ', $this->getEvent()->getRawData(), 4);
			if($nick === $this->getConnection()->getNick())
			{
				foreach(explode(' ', substr(trim($channels), 1)) as $channel)
					$this->delayedData['joined'][$id][] = ltrim($channel, '@+');
			}
		}
		elseif($code === 318)
		{
			sort($this->delayedData['joined'][$id]);
			$this->getPluginHandler()->getPlugin('Ipc')->sendResponse($id, $this->delayedData['joined'][$id]);
			unset($this->delayedData['joined'][$id]);
		}
	}	
}