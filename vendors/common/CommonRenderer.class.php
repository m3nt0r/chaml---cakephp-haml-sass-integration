<?php
/**
 * Common renderer.
 *
 * @link http://haml.hamptoncatlin.com/ Original Common parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */

require_once dirname(__FILE__) . '/CommonElementsList.class.php';

/**
 * Common renderer.
 *
 * @link http://haml.hamptoncatlin.com/ Original Common parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */
abstract class CommonRenderer
{
	/**
	 * List of assigned elements
	 *
	 * @var CommonElementsList
	 */
	protected $elements;

	/**
	 * Get the elements. List of assigned elements
	 *
	 * @return CommonElementsList
	 */
	public function getElements()
	{
		return $this->elements;
	}

	/**
	 * Set the elements. List of assigned elements
	 *
	 * @param CommonElementsList Elements data
	 * @return object

	 */
	public function setElements($elements)
	{
		$this->elements = $elements;
		return $this;
	}

	/**
	 * The contructor.
	 *
	 * @param CommonElementsList Elements assigned to renderer
	 */
	public function __construct(CommonElementsList $elements)
	{
		$this->setElements($elements);
	}

	/**
	 * Render the Common code to CSS
	 *
	 * @return string
	 */
	abstract public function render();

	/**
	 * Instances of CommonRenderer for singleton
	 *
	 * @var array List CommonRenderer instances
	 */
	protected static $instances = array();
	
	/**
	 * Return instance of CommonRenderer. Implements
	 * Singleton pattern.
	 *
	 * @param CommonElementsList Elements assigned to renderer
	 * @param string Common output style
	 * @return CommonRenderer
	 */
	public static function getInstance(CommonElementsList $elements, $type = null)
	{
		if ($type instanceof CommonRenderer)
			return $type->setElements($elements);
		if (!array_key_exists($type, self::$instances))
			self::$instances[$type] = new $type($elements);
		return self::$instances[$type];
	}

	/**
	 * Render the Common code
	 *
	 * @see CommonRenderer::render()
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}
}

?>