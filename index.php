<?php

ini_set('display_errors', 0);
error_reporting(0);

$config = [
	'env' => 'dev',
	'vendorDir' => __DIR__ . '/vendor/',
	'configDir' => __DIR__ . '/config/',
	'cacheDir' => __DIR__ . '/cache/'
];

// Composer
require $config['vendorDir'] . 'autoload.php';

$app = new xorik\cms\App($config, __DIR__, true);

$app->run();
