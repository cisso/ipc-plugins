<?php
/**
 * Provides a very basic framework to draft the webclient interface.
 * It uses "modules" to display information and handle interaction. For an
 * example see Webclient/modules/example.php
 *
 *
 */
class WebClient
{
	protected $address;

	protected $baseDir;

	protected $baseUrl;

	protected $phergiePath;
	
	// Global text for empty values
	protected $notAvailable = '---';
	
	protected $connected = false;
	
	protected $isAjax = false;
	
	// Stores additional HTML for the page's <head>
	protected $head = array();
	
	public function __construct($phergiePath = null)
	{
		$this->isAjax      = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		$this->baseUrl     = dirname($_SERVER['SCRIPT_NAME']) . '/';
		$this->baseDir     = realpath(dirname(__FILE__) . '/..') . '/';
		require_once $this->getPhergieBaseDir() . 'Phergie/Autoload.php';

		Phergie_Autoload::registerAutoloader();

		$bot           = new Phergie_Bot;
		$config        = $bot->getConfig();
		$this->address = $config->offsetGet('webclient.address');
		if($this->send('api', 'hello') === 'Hello!')
			$this->connected = true;
	}
	
	protected function getPhergieBaseDir()
	{
		if(isset($_ENV['PHERGIE_BASEDIR']))
			$baseDir = $_ENV['PHERGIE_BASEDIR'];
		else
		{
			$path = array_reverse(explode(DIRECTORY_SEPARATOR, dirname(__FILE__)), true);
			while((list($index, $dir) = each($path)) && $dir !== 'Phergie')
				unset($path[$index]);
			$baseDir = realpath(implode(DIRECTORY_SEPARATOR, array_reverse($path))  . '/..');
		}
		if(strlen($baseDir) && file_exists($baseDir))
			return rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		throw new Exception('Phergie base directory "' . $baseDir . '" not found');
	}
	

	public function run()
	{
		if($this->isAjax && !empty($_POST))
		{
			list($module, $data) = each($_POST);
			$this->renderModule($module, $data);
		}
		else
			return $this->renderModule('page');
	}
	
	
	public function renderModule($module, $data = array(), $return = false)
	{
		if(basename($module) !== $module)
			throw new Exception('Module name "' . $module . '" is illegal');
		$file = $this->baseDir . "modules/$module.php";
		if(!is_readable($file))
			throw new Exception('Module file for module "' . $module . '" not found');
		$postData = isset($_POST[$module]) ? $_POST[$module] : array();
		return $this->render($file, $data + $postData, $return);
	}
	

	public function render($_template_, $_data_, $return = false)
	{
		$EMPTY = $this->notAvailable;
		if(is_array($_data_))
			extract($_data_, EXTR_PREFIX_SAME, 'data');
		ob_start();
		ob_implicit_flush(false);
		require $_template_;
		$output = ob_get_clean();
		if($return)
			return $output;
		else
			echo $output;
	}
	
	public function addHead($html)
	{
		$this->head[] = $html;
	}
	

	public function sendRaw($commandRaw)
	{
		$commandRaw = trim($commandRaw);
		// Allow whitespace inside quotes
		preg_match_all('/"[^"]*"|[^" ]+/', $commandRaw, $args);
		$args = reset($args);
		foreach(array_keys($args) as $index)
			$args[$index] = trim($args[$index], '"');
		$api = array_shift($args);
		$command = array_shift($args);
		if(!$api || !$command)
			return false;
		return $this->send($api, $command, $args);
	}
	

	/**
	 * Debugging can be enabled by appending one or more of the following flags to
	 * $api:
	 *   !time   The time taken for the request and the start time
	 *   !size   The length of the returned string
	 *   !valid  Wether the returned string could be unserialized
	 *
	 * Example: $api = 'myApi!time!size!valid'
	 */
	public function send($api, $command, $args = null)
	{
		$debug = explode('!', $api);
		$api   = array_shift($debug);
		
		$result      = null;
		$startTime   = time();
		$debugOutput = array();
		
		if($fp = @stream_socket_client("tcp://$this->address", $errcode, $errstr, 2))
		{
			fwrite($fp, serialize(array($api, $command, $args)));
			$result = stream_get_contents($fp);
			fclose($fp);
		}

		if(in_array('size', $debug))
			$debugOutput['size'] = strlen($result);
		if(in_array('valid', $debug))
			$debugOutput['valid'] = $result === serialize(false);

		$result = @unserialize($result);

		if(in_array('valid', $debug))
			$debugOutput['valid'] = (int) ($debugOutput['valid'] || $result !== false);
		if(in_array('time', $debug))
			$debugOutput['time'] = time() - $startTime . ' seconds, started ' . date('H:i:s', $startTime);
			
		if(!empty($debugOutput))
			$result = $debugOutput + array('result' => $result);

		return $result;
	}
	
}
