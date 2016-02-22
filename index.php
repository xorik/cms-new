<?php

ini_set('display_errors', 0);
error_reporting(-1);

// Composer
require 'vendor/autoload.php';

// Try to get config
if (is_file($file = __DIR__ . '/config.php')) {
	$config = require $file;
} else {
	$config = ['env' => 'prod'];
}

// Check environment
if (!isset($config['env']) || !in_array($config['env'], ['dev', 'test', 'prod'])) {
	die('Please set env to one of (dev,test,prod) in file: ' . $file);
}

$app = new \Slim\App(['config' => $config]);

// Set error handler
$container = $app->getContainer();
$container['cmsErrorHandler'] = new \xorik\cms\ErrorHandler($container);

$app->run();
