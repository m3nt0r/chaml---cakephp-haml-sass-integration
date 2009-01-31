<?php
/**
 * Cache engine
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */

/**
 * Cache engine
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */
class CommonCache
{
	/**
	 * The constructor
	 *
	 * @param string Path to cached data
	 * @param string Extension of cache files
	 * @param string Data to cache
	 */
	public function __construct($path, $extension = 'ccd', $data)
	{
		$this->setPath($path);
		$this->setExtension($extension);
		$this->setData($data);
		$this->setHash($this->createHash($data));
		if (file_exists($this->getFilename()))
			$this->setCached(file_get_contents($this->getFilename()));
	}
	
	/**
	 * Extension
	 *
	 * @var string
	 */
	protected $extension;
	
	/**
	 * Get the extension. Extension
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}
	
	/**
	 * Set the extension. Extension
	 *
	 * @param string Extension data
	 * @return object
	 */
	public function setExtension($extension)
	{
		$this->extension = $extension;
		return $this;
	}
	
	/**
	 * Cached data
	 *
	 * @var string
	 */
	protected $cached = null;
	
	/**
	 * Get the cached. Cached data
	 *
	 * @return string
	 */
	public function getCached()
	{
		return $this->cached;
	}
	
	/**
	 * Get the cached. Cached data
	 *
	 * @see CommonCache::getCached()
	 * @return string
	 */
	public function __toString()
	{
		return $this->getCached();
	}
	
	/**
	 * Set the cached. Cached data
	 *
	 * @param string Cached data
	 * @return object
	 */
	public function setCached($cached)
	{
		$this->cached = ltrim($cached);
		return $this;
	}
	
	/**
	 * Check for cached data
	 *
	 * @return unknown
	 */
	public function isCached()
	{
		return !is_null($this->cached);
	}
	
	/**
	 * Data
	 *
	 * @var string
	 */
	protected $data;
	
	/**
	 * Get the data. Data
	 *
	 * @return string
	 */
	public function getData()
	{
		return $this->data;
	}
	
	/**
	 * Set the data. Data
	 *
	 * @param string Data data
	 * @return object
	 */
	public function setData($data)
	{
		$this->data = $data;
		return $this;
	}
	
	/**
	 * Path
	 *
	 * @var string
	 */
	protected $path;
	
	/**
	 * Get the path. Path
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
	
	/**
	 * Set the path. Path
	 *
	 * @param string Path data
	 * @return object
	 */
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}
	
	/**
	 * Hash of data
	 *
	 * @var string
	 */
	protected $hash;
	
	/**
	 * Get the hash. Hash of data
	 *
	 * @return string
	 */
	public function getHash()
	{
		return $this->hash;
	}
	
	/**
	 * Set the hash. Hash of data
	 *
	 * @param string Hash data
	 * @return object
	 */
	protected function setHash($hash)
	{
		$this->hash = $hash;
		$this->setFilename($this->getPath() . "/$hash." . $this->getExtension());
		return $this;
	}
	
	/**
	 * Create hash of data
	 *
	 * @param string Data
	 * @return string
	 */
	protected function createHash($data = null)
	{
		if (is_null($data))
			$data = $this->getData();
		if (function_exists('hash'))
			return hash('md5', $data);
		else
			return md5($data);
	}
	
	/**
	 * Cache filename
	 *
	 * @var string
	 */
	protected $filename;
	
	/**
	 * Get the filename. Cache filename
	 *
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}
	
	/**
	 * Set the filename. Cache filename
	 *
	 * @param string Filename data
	 * @return object
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;
		return $this;
	}
	
	/**
	 * Cache data
	 *
	 * @return CommonCache
	 */
	public function cacheIt()
	{
		file_put_contents($this->getFilename(), $this->getCached());
		return $this;
	}
}

?>