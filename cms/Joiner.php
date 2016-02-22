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