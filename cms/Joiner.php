<?php
namespace xorik\cms;

/**
 * Join PHP files and configs
 */
class Joiner
{
	const GLOB = '*/*/cms/';

	protected $c;
	protected $app;
	protected $isDev;
	protected $vendorDir, $cacheDir, $configDir;

	public function __construct($c, $app)
	{
		$this->c = $c;
		$this->app = $app;
		$this->isDev = $c->config['env'] == 'dev';
		$this->vendorDir = $c->config['vendorDir'];
		$this->cacheDir = $c->config['cacheDir'];
		$this->configDir = $c->config['configDir'];
	}

	public function run($fileName)
	{
		// Prepare container and app for required files
		$container = $this->c;
		$app = $this->app;

		if (!$this->isDev) {
			if (!is_file($cacheFile = $this->cacheDir . $fileName . '.cache.php')) {
				// Generate cache file
				$this->joinPHP($fileName, $cacheFile);
			}

			require $cacheFile;
		} else {
			// Require each file
			foreach (glob($this->vendorDir . self::GLOB . $fileName . '.php') as $f) {
				require $f;
			}

			// Require file from configDir, if exists
			if (is_file($file = $this->configDir . $fileName . '.php')) {
				require $file;
			}
		}
	}

	public function config($fileName)
	{
		if (!$this->isDev) {
			if (!is_file($configFile = $this->cacheDir . $fileName . '.cache.php')) {
				// Generate cache file
				$this->joinConfig($fileName, $configFile);
			}

			return require $configFile;
		} else {
			return $this->getConfig($fileName);
		}
	}

	protected function joinPHP($fileName, $cacheFile)
	{
		// Try to create cache file
		$f = fopen($cacheFile, 'w');

		if ($f === false) {
			die('Error opening file to write:' . $cacheFile);
		}

		fputs($f, '<?php' . "\n");

		// Get each file and remove <?php at the begin
		foreach (glob($this->vendorDir . self::GLOB . $fileName . '.php') as $file) {
			$this->putPHP($f, $file);
		}

		if (is_file($file = $this->configDir . $fileName . '.php')) {
			$this->putPHP($f, $file);
		}

		fclose($f);
	}

	public function joinConfig($fileName, $configFile)
	{
		$config = $this->getConfig($fileName);

		// Try to create cache file
		$f = fopen($configFile, 'w');

		if ($f === false) {
			die('Error opening file to write:' . $configFile);
		}

		fputs($f, '<?php' . "\n" . 'return ');
		fputs($f, var_export($config, true));
		fputs($f, ';');

		fclose($f);
	}

	protected function getConfig($fileName)
	{
		$container = $this->c;

		$config = [];
		// Require each file
		foreach (glob($this->vendorDir . self::GLOB . $fileName . '.php') as $f) {
			$new = require $f;
			$config = array_replace_recursive($config, $new);
		}

		// Require file from configDir, if exists
		if (is_file($file = $this->configDir . $fileName . '.php')) {
			$new = require $file;
			$config = array_replace_recursive($config, $new);
		}

		return $config;
	}

	/**
	 * Get PHP code from file, remove <?php from the begin and put to the file resource
	 *
	 * @param Resource $f opened file
	 * @param string $file filename to read
	 */
	protected function putPHP($f, $file) {
		$content = file_get_contents($file);
		if ($content === false) {
			die('Error opening file: ' . $file);
		}

		$content = preg_replace('/^<\?php/', '', $content);

		// Add comment (filename)
		$file = str_replace($this->c->rootDir, '', $file);
		fputs($f, "\n// $file\n");

		fputs($f, $content);
	}
}