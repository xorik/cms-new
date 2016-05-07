<?php
namespace xorik\cms;

class App
{
	/** @var \Slim\App */
	protected static $app;
	/** @var \Interop\Container\ContainerInterface */
	protected static $ci;

	public function __construct($config, $rootDir, $dispatchMiddleware=false)
	{
		// Check environment
		if (!isset($config['env']) || !in_array($config['env'], ['dev', 'test', 'prod'])) {
			die('Please set env to one of (dev,test,prod) in index.php');
		}

		// Start the App
		$data = ['config' => $config];
		if ($dispatchMiddleware) {
			$data['settings'] = ['determineRouteBeforeAppMiddleware' => true];
		}
		self::$app = new \Slim\App($data);

		// Configure file root and http root
		self::$ci = self::$app->getContainer();
		self::$ci['rootDir'] = $rootDir . '/';

		if (isset($_SERVER['SCRIPT_NAME'])) {
			self::$ci['httpRoot'] = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
		}

		// Prepare config
		$joiner = self::$ci['joiner'] = new Joiner(self::$ci);
		$new_config = $joiner->config('config');

		// Add values from local config and modules
		self::$ci['config'] = array_replace($config, $new_config);

		// Use simple routing strategy
		self::$ci['foundHandler'] = function()
		{
			return new SimpleStrategy();
		};

		// Run init functions from modules and local file
		$joiner->run('init');

		// Prepare CI
		$joiner->run('ci');

		// Prepare routes
		$joiner->run('route', ['app' => self::$app]);
	}

	public function run()
	{
		self::$app->run();
	}

	/**
	 * @return \Interop\Container\ContainerInterface
	 */
	public static function getContainer()
	{
		return self::$ci;
	}

	/**
	 * @return \Slim\App
	 */
	public static function getApp()
	{
		return self::$app;
	}
}