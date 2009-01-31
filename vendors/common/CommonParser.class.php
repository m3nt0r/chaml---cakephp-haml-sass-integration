<?php
/**
 * Common parser.
 *
 * @link http://haml.hamptoncatlin.com/ Original Common parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */

require_once dirname(__FILE__) . '/CommonElement.class.php';
require_once dirname(__FILE__) . '/CommonElementsList.class.php';

/**
 * Common parser.
 *
 * @link http://haml.hamptoncatlin.com/ Original Common parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */
abstract class CommonParser
{
	/**
	 * Render source
	 *
	 * @param string Filename
	 * @param string Renderer class
	 * @return string
	 */
	abstract public function render($file = null, $renderer = null);
	
	/**
	 * Render source
	 *
	 * @see CommonParser::render()
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}
	
	/**
	 * Display source
	 * 
	 * @param string Filename
	 * @param string Renderer class
	 * @see CommonParser::render()
	 * @return CommonParser
	 */
	public function display($file = null, $renderer = null)
	{
		echo $this->render($file, $renderer);
		return $this;
	}
	
	/**
	 * Render sourc
	 *
	 * @param string Filename
	 * @param string Renderer class
	 * @see CommonParser::render()
	 * @return string
	 */
	public function fetch($file = null, $renderer = null)
	{
		return $this->render($file, $renderer);
	}

	/**
	 * List of elements
	 *
	 * @var CommonElementsList
	 */
	protected $elements;

	/**
	 * Get the elements. List of elements
	 *
	 * @return CommonElementsList
	 */
	public function getElements()
	{
		return $this->elements;
	}

	/**
	 * Set the elements. List of elements
	 *
	 * @param CommonElementsList Elements data
	 * @return object
	 */
	public function setElements(CommonElementsList $elements)
	{
		$this->elements = $elements;
		return $this;
	}

	/**
	 * Add element
	 *
	 * @param CommonElement Element
	 * @return object
	 */
	public function addElement(CommonElement $element)
	{
		$this->elements->append($element);
		return $this;
	}
	
	/**
	 * Clean line from TOKEN_INDENT
	 *
	 * @param string Line
	 * @return string
	 */
	public function cleanLine($line)
	{
		return ltrim($line, self::TOKEN_INDENT);
	}

	/**
	 * Count level of line
	 *
	 * @param string Line
	 * @return integer
	 */
	public function countLevel($line)
	{
		return ceil((strlen($line) - strlen($this->cleanLine($line))) / self::INDENT);
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
	 * Temporary directory
	 *
	 * @var string
	 */
	protected $tmp;

	/**
	 * Get the tmp. Temporary directory
	 *
	 * @return string
	 */
	public function getTmp()
	{
		return $this->tmp;
	}

	/**
	 * Set the tmp. Temporary directory
	 *
	 * @param string Tmp data
	 * @return object
	 */
	public function setTmp($tmp)
	{
		$this->tmp = $tmp;
		return $this;
	}

	/**
	 * File assigned to Common parser
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * Get the file. File assigned to Common parser
	 *
	 * @return string
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * Set the file. File assigned to Common parser
	 *
	 * @param string file data
	 * @return object
	 */
	public function setFile($file)
	{
		if (!file_exists($file))
			if (!file_exists($file = $this->getPath()."/$file"))
				throw new CommonException("File '$file' don't exists");
		$this->file = $file;
		$this->setSource(file_get_contents($this->file));
		return $this;
	}

	/**
	 * Common source
	 *
	 * @var string
	 */
	protected $source;

	/**
	 * Get the source. Common source
	 *
	 * @return string
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * Set the source. Common source
	 *
	 * @param string Source data
	 * @return object
	 */
	public function setSource($source)
	{
		$this->source = $source;
		return $this;
	}

	/**
	 * Type of CommonRenderer
	 *
	 * @see CommonRenderer
	 * @var string
	 */
	protected $renderer = null;

	/**
	 * Get the renderer. Type of CommonRenderer
	 *
	 * @return string
	 */
	public function getRenderer()
	{
		return $this->renderer;
	}

	/**
	 * Set the renderer. Type of CommonRenderer
	 *
	 * @param string Renderer data
	 * @return object
	 */
	public function setRenderer($renderer)
	{
		$this->renderer = $renderer;
		return $this;
	}
	
	const INDENT = 2;

	const TOKEN_INDENT = ' ';
}

?>