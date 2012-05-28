<?php
class Phergie_Plugin_SystemApi extends Phergie_Plugin_Abstract
{
	protected $db;
	protected $memoryLastUsage;
	protected $memoryLastCheck;
	protected $lastPing;
	protected $ticks = array(
		'last'  => 0,
		'times' => array(),
	);
	protected $startTime;
	protected $init = false;
	
	public function onLoad()
	{
		// Required for ipcReload()
		if(!$this->startTime)
			$this->startTime = time();
			
		$info = $this->getPluginHandler()->getPluginInfo('SystemApi');
		list( , , $baseDir) = explode('/', $info['dir'], 3);
		
		$this->db = $this->getPluginHandler()->getPlugin('Db')->init($baseDir . '/SystemApi/', 'systemapi.db', 'systemapi.sqlite');
		$this->getIpc()->addApi($this);
	}
	
	public function ipcAlias()
	{
		return 'system';
	}
	
	public function ipcCommands()
	{
		return array(
			'memory' => 'apiMemory',
			'global' => 'apiGlobal',
			'ticks'  => 'apiTicks',
			'runtime' => 'apiRuntime',
			'lastping' => 'apiLastPing',
		);
	}
	
	public function ipcReload($instance)
	{
		$this->startTime = $instance->getStartTime();
		$this->ticks = $instance->getTicks();
	}
	
	public function getTicks()
	{
		return $this->ticks;
	}
	
	public function getStartTime()
	{
		return $this->startTime;
	}


	public function apiStartTime()
	{
		return $this->startTime;
	}
	
	public function apiRuntime()
	{
		return time() - $this->startTime;
	}
	
	public function apiLastPing()
	{
		return ($this->lastPing ? (time() - $this->lastPing) : null);
	}
	
	
	public function apiTicks($since = null)
	{
		if(empty($since))
			return array_sum($this->ticks['times']) / count($this->ticks['times']);
		$since = time() - 480;
		$result = array();
		foreach($this->ticks['times'] as $time => $ticks)
			if($time >= $since)
				$result[$time] = $ticks;

		return $result;
	}
	
	public function apiGlobal($name = null)
	{
		if(in_array($name, array('server', 'get', 'post', 'files', 'cookie', 'session', 'request', 'env')))
			$name = '_' . strtoupper($name);
		if(!isset($GLOBALS[$name]))
			return false;
		if(is_object($GLOBALS[$name]))
			return 'Instance of ' . get_class($GLOBALS[$name]);
		
		return $GLOBALS[$name];
	}

	
	public function apiMemory($since = null)
	{
		if(empty($since))
			return memory_get_usage();
		$query = '
			SELECT *
			FROM memory_usage WHERE time > ?
		';
		$st = $this->db->prepare($query);
		$st->execute(array((int) $since));
		$rows = $st->fetchAll(PDO::FETCH_ASSOC);
		$values = array();
		foreach($rows as $index => $row)
		{
			$values[$row['time']] = $row['size'];
			unset($result[$index]);
		}
		return $values;
	}
	
	public function onTick()
	{
		$this->logTicks();
		$this->logMemory();
	}
	
	public function onPing()
	{
		$this->lastPing = time();
	}
	
	protected function logMemory()
	{
		$time = time();
		
		if($this->memoryLastCheck >= $time)
			return;

		$this->memoryLastCheck = $time;
		if($this->memoryLastUsage === $memory = memory_get_usage())
			return;

		$this->memoryLastUsage = $memory;
		$this->writeData('memory_usage', array('time' => $time, 'size' => $memory));
	}
	
	protected function logTicks()
	{
		$time = time();
		if($this->ticks['last'] < $time)
		{
			$this->ticks['times'][$time] = 0;
			$this->ticks['last'] = $time;
			while(!is_null($key = key($this->ticks['times'])) && ($key < ($time - 600)))
				unset($this->ticks['times'][$key]);
		}
		$this->ticks['times'][$time]++;
	}
	
	protected function writeData($table, $data)
	{
		$values = array();
		foreach($data as $field => $value)
			$values[":$field"] = $value;
		
		$fields = implode(',', array_keys($data));
		$placeholders = implode(',', array_keys($values));
		$query = "INSERT INTO $table ($fields) VALUES ($placeholders)";
		$statement = $this->db->prepare($query);
		return $statement->execute($data);
	}
		
	protected function getDb($init = false)
	{
		$db = $this->getPluginHandler()->getPlugin('Db');
		if($init)
			;
		return $db;
	}
	
	
	protected function getIpc()
	{
		return $this->getPluginHandler()->getPlugin('Ipc');
	}
	
}