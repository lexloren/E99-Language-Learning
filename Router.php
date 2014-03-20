<?php

require_once "./backend/connection.php";
require_once "./backend/classes/session.php";

// Simple router routes url XXX/YYY to XXX class's YYY method
class Router 
{
	public static function route() 
	{
		$uri = $_SERVER['REQUEST_URI'];
		
		// remove query string from URI
		if (strpos($uri, '?') != false) 
			$uri = strtok($uri,'?');
		
		$uri = trim($uri, '/');
		$uri = trim($uri, ' ');
		
		if(empty($uri))
			return;
	
		$segments = explode('/', $uri);
		
		if (sizeof($segments) == 0 || !isset($segments[0]))
			return;
			
		if (sizeof($segments) != 2)
		{
			self::__404();
		}
			
		$class = 'API'.$segments[0];
		$method = $segments[1];
		
		self::invoke($class, $method);
	}

	public static function route2() 
	{
		$uri = $_SERVER['REQUEST_URI'];
		
		// remove query string from URI
		if (strpos($uri, '?') != false) 
			$uri = strtok($uri,'?');
		
		$uri = trim($uri, '/');
		$uri = trim($uri, ' ');
		$uri = trim($uri, '.php');
		
		if(empty($uri))
			return;
	
		$segments = explode('_', $uri);
		
		if (sizeof($segments) == 0 || !isset($segments[0]))
			return;
			
		if (sizeof($segments) != 2)
		{
			self::__404();
		}
			
		$class = 'API'.ucfirst($segments[0]);
		$method = $segments[1];
		
		self::invoke($class, $method);
	}
	
	private static function invoke($className, $methodName) 
	{
		include(__DIR__ . "/apis/" . $className . ".php");

		try 
		{
			$class = new ReflectionClass($className);
		} 
		catch (ReflectionException $e) 
		{
			self::__404();
		}

		//find the user
		//$user = Session::reauthenticate();
		$link = Connection::get_shared_instance();
		$user = null;
		$instance = $class->newInstance($user, $link);
		
		try 
		{
			$method = $class->getMethod($methodName);

			if ($method->isProtected() or $method->isPrivate()) 
			{
				self::__404();
			}
		}
		catch (ReflectionException $e) 
		{
			self::__404();
		}
		$method->invoke($instance);
		exit;
	}
	
	private static function __404() 
	{
		header('HTTP/1.1 404 File Not Found');
		echo '<h1>'.$_SERVER['REQUEST_URI']. ' not found<h1>';
		exit;
	}
}

?>