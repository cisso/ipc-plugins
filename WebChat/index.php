<?php
define('WEBCHAT_UNKNOWN_ACTION', 'WEBCHAT_UNKNOWN_ACTION');

function webChatSend($address, $api, $command, $args = null)
{
	$result = null;
	if($fp = @stream_socket_client("tcp://$address", $errcode, $errstr, 2))
	{
		fwrite($fp, serialize(array($api, $command, $args)));
		$result = stream_get_contents($fp);
		fclose($fp);
	}
	return @unserialize($result);
}

function webChatAction($address, $action, $data)
{
	switch($action)
	{
		case 'joined':
			return webChatSend($address, 'chan', 'joined');
		
		case 'names':
			return webChatSend($address, 'chan', 'names', array($data));
		
		case 'join':
			$channel = isset($data['channel']) ? trim($data['channel']) : null;
			if(strlen($channel))
				return webChatSend($address, 'bot', 'do', array('JOIN',  $channel));
			return false;

		case 'part':
			$channel = isset($data['channel']) ? trim($data['channel']) : null;
			if(strlen($channel))
				return webChatSend($address, 'bot', 'do', array('PART', $channel));
			return false;

		case 'get':
			return webChatSend($address, 'chat', 'get');

		case 'send':
			$data = array_map('trim', $data + array('channel' => null, 'msg' => null));
			if(strlen($data['channel']) && strlen($data['msg']))
			{
				return webChatSend($address, 'chat', 'send', array($data['channel'], $data['msg']));
			}
			return false;
		
		case 'connection':
			return webChatSend($address, 'bot', 'connection');
	}
	return WEBCHAT_UNKNOWN_ACTION;
}


function webChatBaseDir()
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


$isAjax      = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$debug       = isset($_GET['debug']);
$baseUrl     = dirname($_SERVER['SCRIPT_NAME']) . '/';
$baseDir     = webChatBaseDir();
require_once $baseDir . 'Phergie/Autoload.php';

Phergie_Autoload::registerAutoloader();

$bot       = new Phergie_Bot;
$config    = $bot->getConfig();
$address   = $config->offsetGet('webclient.address');
$connected = webChatSend($address, 'api', 'hello') === 'Hello!';

$data = (isset($_POST) ? $_POST : array()) + array('action' => null, 'args' => null);

if($debug)
	$data = (isset($_GET) ? $_GET : array()) + array('action' => null, 'args' => null);

if($isAjax || $debug)
{
	if(!$connected)
		exit(json_encode(null));

	$response = null;
	if(!is_null($data['action']))
		$result = webChatAction($address, $data['action'], $data['args']);
	if($result !== WEBCHAT_UNKNOWN_ACTION)
		$response = $result;
	
	if($debug)
		exit('<pre>' . print_r($response, true) . '</pre>');
	
	exit(json_encode($response));
}

include('page.tpl.php');
