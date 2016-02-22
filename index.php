<?php

ini_set('display_errors', 0);
error_reporting(-1);

// Try to get config
if (is_file($file = __DIR__ . '/config.php')) {
	$config = require $file;
}

$default_config = [
	'env' => 'prod',
	'vendorDir' => __DIR__ . '/vendor/',
	'configDir' => __DIR__ . '/config/',
	'cacheDir' => __DIR__ . '/cache/'
];

// Merge default config and config
$config = isset($config) ? array_replace($default_config, $config) : $default_config;

// Composer
require $config['vendorDir'] . 'autoload.php';

// Check environment
if (!isset($config['env']) || !in_array($config['env'], ['dev', 'test', 'prod'])) {
	die('Please set env to one of (dev,test,prod) in file: ' . $file);
}

$app = new \Slim\App(['config' => $config, 'rootDir' => __DIR__ . '/']);

// Set error handler
$container = $app->getContainer();
$container['cmsErrorHandler'] = new \xorik\cms\ErrorHandler($container);

// Prepare config
$joiner = $container['cmsJoiner'] = new \xorik\cms\Joiner($container, $app);
$new_config = $joiner->config('config');

// Add values from local config and modules
$container['config'] = array_replace($config, $new_config);

// Prepare CI
$joiner->run('ci');

// Prepare routes
$joiner->run('route');

$app->run();
