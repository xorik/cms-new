<?php

ini_set('display_errors', 0);
error_reporting(0);

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

// Start the App
$app = new \Slim\App(['config' => $config]);

// Configure file root and http root
$container = $app->getContainer();
$container['rootDir'] = __DIR__ . '/';
if (isset($_SERVER['SCRIPT_NAME'])) {
	$container['httpRoot'] = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
}

// Use simple routing strategy
$container['foundHandler'] = function()
{
	return new \xorik\cms\SimpleStrategy();
};

// Prepare config
$joiner = $container['cmsJoiner'] = new \xorik\cms\Joiner($container);
$new_config = $joiner->config('config');

// Add values from local config and modules
$container['config'] = array_replace($config, $new_config);

// Run init functions from modules and local file
$joiner->run('init');

// Prepare CI
$joiner->run('ci');

// Prepare routes
$joiner->run('route', ['app' => $app]);

$app->run();
