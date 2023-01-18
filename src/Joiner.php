<?php
namespace xorik\cms;

use Rmoiseev\Spyc\Spyc;

/**
 * Join PHP files and configs
 */
class Joiner
{
	const GLOB = '*/*/cms/';

	protected $c;
	protected $isDev;
	protected $vendorDir, $cacheDir, $configDir;

	public function __construct($c)
	{
		$this->c = $c;
		$this->isDev = $c->config['env'] == 'dev';
		$this->vendorDir = $c->config['vendorDir'];
		$this->cacheDir = $c->config['cacheDir'];
		$this->configDir = $c->config['configDir'];
	}

	public function run($fileName, $data=null)
	{
		// Prepare container and data for required files
		$container = $this->c;

		if ($data) {
			extract($data);
		}

		if (!$this->isDev) {
			if (!is_file($cacheFile = $this->cacheDir . $fileName . '.cache.php')) {
				// Generate cache file
				$this->joinPHP($fileName, $cacheFile);
			}

			require $cacheFile;
		} else {
			// Require each file
			foreach ($this->getGlob($fileName) as $f) {
				require $f;
			}
		}
	}

	public function config($fileName, $cache=true)
	{
		if ($cache && !$this->isDev) {
			if (!is_file($configFile = $this->cacheDir . $fileName . '.cache.php')) {
				// Generate cache file
				$config = $this->getConfig($fileName);
				$this->saveConfig($config, $configFile);
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
		foreach ($this->getGlob($fileName) as $file) {
			$this->putPHP($f, $file);
		}

		fclose($f);
	}

	public function saveConfig($data, $fileName)
	{
		// Try to create cache file
		$f = fopen($fileName, 'w');

		if ($f === false) {
			die('Error opening file to write:' . $fileName);
		}

		fputs($f, '<?php' . "\n" . 'return ');
		fputs($f, var_export($data, true));
		fputs($f, ';');

		fclose($f);
	}

	protected function getConfig($fileName)
	{
		$container = $this->c;

		$config = [];
		// Require each file
		foreach ($this->getGlob($fileName) as $f) {
			$ext = pathinfo($f, PATHINFO_EXTENSION);

			if ($ext == 'php') {
				$new = require $f;
			} elseif ($ext == 'yml') {
				$new = Spyc::YAMLLoad($f);
			} else {
				die("Incorrect file extension: $f");
			}
			$config = array_replace_recursive($config, $new);
		}

		return $config;
	}

	protected function getGlob($fileName)
	{
		// Path => priority
		$list = [];

		// Add files from vendorDir
		foreach (glob($this->vendorDir . self::GLOB . $fileName . '*') as $f) {
			$list[$f] = 0;
		}

		// Add files from configDir, by default add after vendor
		foreach (glob($this->configDir . $fileName . '*') as $f) {
			$list[$f] = -1;
		}

		// Prepare preg
		$preg = '/\/' . preg_quote($fileName) . '(?:\.(-?\d+))?\.(?:php|yml)$/';

		// Check if file is ours
		foreach ($list as $f=>&$prio) {
			if (!preg_match($preg, $f, $m)) {
				unset($list[$f]);
			} elseif (isset($m[1])) {
				$prio = (int)$m[1];
			}
		}

		// Sort list
		arsort($list);

		// Return files in priority order
		return array_keys($list);
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

		$content = preg_replace('/^<\?php\s+/', '', $content);

		// Add comment (filename)
		$file = str_replace($this->c->rootDir, '', $file);
		fputs($f, "\n\n// $file\n");

		fputs($f, $content);
	}
}
