<?php
/**
 * Stuff that will burn you. Use at your own risk.
 */
class Phergie_Plugin_AlphaApi extends Phergie_Plugin_Abstract
{
	public $logStatus = false;
	public $logContent = array();
	protected $delayedData = array(
		'data'     => array(),
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
		return 'aa';
	}

	
	public function ipcCommands()
	{
		return array(
			'log'      => 'apiLog',
			'data'     => 'apiDummyData'
		);
	}


	public function onTick()
	{
		$this->handleDummyData();
	}

	
	public function preEvent()
	{
		$this->handleLogs();
	}
	
	public function apiDummyData($length, $delay = 0)
	{
		if($delay <= 0)
			return str_pad('', $length, '.');

		$id = $this->getPluginHandler()->getPlugin('Ipc')->delayResponse();
		$this->delayedData['data'][$id] = array(time(), $length, $delay);
	}

	
	protected function handleDummyData()
	{
		if(empty($this->delayedData['data']))
			return;
		
		foreach(array_keys($this->delayedData['data']) as $id)
		{
			list($time, $length, $delay) = $this->delayedData['data'][$id];
			if($time + $delay > time())
				continue;
			$data = $this->apiDummyData($length);
			$this->getPluginHandler()->getPlugin('Ipc')->sendResponse($id, $data);
			unset($this->delayedData['data'][$id]);
		}
	}

	
	public function apiLog($action)
	{
		$count = count($this->logContent);
		$args = func_get_args();
		array_shift($args);

		switch($action)
		{
			case 'start':
				$this->logStatus = true;
				return true;
				
			case 'stop':
				$this->logStatus = false;
				return $count;
			
			case 'clear':
				$this->logContent = array();
				return $count;

			case 'status':
				return $this->logStatus;

			case 'count':
				return $count;
			
			case 'tail':
				$lines = is_null($args[0]) ? $count : $args[0];
				$excerpt = array_slice($this->logContent, -$lines, $lines, true);
				return $excerpt;

			case 'head':
				$lines = is_null($args[0]) ? $count : $args[0];
				$excerpt = array_slice($this->logContent, 0, $lines, true);
				return $excerpt;
		}
	}
		
	
	protected function handleLogs()
	{
		if(!$this->logStatus)
			return;
		
		list($micro, $time) = explode(' ', microtime());
		$event = $this->getEvent();
		$host = method_exists($event, 'getHostMask')
			? $event->getHostmask()->getHost() . ' -- '
			: '';
		$data = $this->getEvent()->getRawData();
		
		$this->logContent[$time.ltrim($micro, '0')] = $host . $data;
	}
	
}