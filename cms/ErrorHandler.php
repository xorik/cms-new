<?php
namespace xorik\cms;


class ErrorHandler
{
	/** @var \Whoops\Run $whoops */
	protected $whoops;

	public function __construct($c)
	{
		// Use Slim error handlers if not dev environment
		if ($c->config['env'] != 'dev') {
			return;
		}

		// Unset Slim error handler on dev environment
		unset($c['errorHandler']);

		if (class_exists('\Whoops\Run')) {
			// If whoops is loaded, add his handlers
			$this->whoops = new \Whoops\Run;
			$this->whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
			$this->whoops->register();
		} else {
			// Else PHP/xdebug error handlers
			ini_set('display_errors', 1);
			error_reporting(E_ALL);
		}
	}

	public function handleException(\Exception $e)
	{

	}
}