<?php
class Phergie_Plugin_WebChat extends Phergie_Plugin_Abstract
{
	protected $apiEvents = array();
	protected $delayedData = array();
	
	
	public function onLoad()
	{
		$address = $this->getConfig('webclient.address', '127.0.0.1:7654');
		$this->getConfig()->offsetSet('ipc.address', $address);
		$this->getPluginHandler()
			->getPlugins(array(
				'WebClient',
				'AlphaApi'
			));
		$this->getPluginHandler()->getPlugin('Ipc')->addApi($this);
	}
	
	public function ipcReload($instance)
	{
		$this->apiEvents = $instance->getEvents();
		$this->delayedData = $instance->getDelayedData();
	}
	
	public function getEvents()
	{
		return $this->apiEvents;
	}

	public function getDelayedData()
	{
		return $this->delayedData;
	}
	
	public function ipcAlias()
	{
		return 'chat';
	}
	
	public function ipcCommands()
	{
		return array(
			'get'      => 'apiGetEvents',
			'send'     => 'apiSendMsg',
		);
	}
		
	
	public function apiGetEvents($clear = true)
	{
		if(count($this->apiEvents))
		{
			$data = $this->apiEvents;
			if($clear)
				$this->apiEvents = array();
			return $data;
		}
		$id = $this->getPluginHandler()->getPlugin('Ipc')->delayResponse();
		$this->delayedData[$id] = $clear;
	}
	
	
	public function apiSendMsg($target, $msg)
	{
		$this->doPrivmsg($target, $msg);
	}
	
	
	public function handleEvent($type)
	{
		switch($type)
		{
			case 'onJoin':
				$data = $this->getEventData();
				$data['channel'] = $data['args'][0];
				$this->apiEvents[] = $data;
				break;

			case 'onPart':
				$data = $this->getEventData();
				$data['channel'] = $data['args'][0];
				$this->apiEvents[] = $data;
				break;

			case 'onQuit':
				$data = $this->getEventData();
				$this->apiEvents[] = $data;
				break;
			
			case 'onNotice':
			case 'onPrivmsg':
				$data = $this->getEventData();
				$data['channel'] = $data['args'][0];
				$data['msg'] = $data['args'][1];
				$this->apiEvents[] = $data;
				break;
				
			default:
				return;
		}
		
		if(count($this->delayedData) && count($this->apiEvents) && end($this->delayedData))
		{
			$id = key($this->delayedData);
			$data = $this->apiGetEvents();
			$this->getPluginHandler()->getPlugin('Ipc')->sendResponse($id, $data);
		}
	}
	
	protected function getEventData()
	{
		$event = $this->getEvent();
		$hostmask = $event->getHostmask();
		$data = array(
			'time'     => time(),
			'nick'     => $hostmask->getNick(),
			'username' => $hostmask->getUsername(),
			'host'     => $hostmask->getHost(),
			'args'     => $event->getArguments(),
			'type'     => $event->getType(),
			'tokens'   => array()
		);
		return $data;
	}
	
	
	public function postDispatch()
	{
		try
		{
			$currentEvent = $this->getEvent();
		}
		catch (Exception $e)
		{
			$currentEvent = null;
		}
		$events = $this->getEventHandler()->getEvents();
		foreach($events as $event)
		{
			switch($event->getType())
			{
				case 'privmsg':
					$event->setHostmask($this->getConnection()->getHostmask());
					$this->setEvent($event);
					$this->handleEvent('on' . ucfirst($event->getType()));
					break;
			}
		}
		$this->setEvent($currentEvent);
	}
	
	
	public function onPrivmsg() { $this->handleEvent(__FUNCTION__); }
	public function onNotice() { $this->handleEvent(__FUNCTION__); }
	public function onTopic() { $this->handleEvent(__FUNCTION__); }
	public function onOper() { $this->handleEvent(__FUNCTION__); }
	public function onQuit() { $this->handleEvent(__FUNCTION__); }
	public function onJoin() { $this->handleEvent(__FUNCTION__); }
	public function onPart() { $this->handleEvent(__FUNCTION__); }
	public function onMode() { $this->handleEvent(__FUNCTION__); }
	public function onAction() { $this->handleEvent(__FUNCTION__); }
	public function onKick() { $this->handleEvent(__FUNCTION__); }
	public function onInvite() { $this->handleEvent(__FUNCTION__); }
	public function onResponse() { $this->handleEvent(__FUNCTION__); }
	
}