<?php
/**
 * Sass element.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass
 */

require_once dirname(__FILE__) . '/../common/CommonElement.class.php';
require_once dirname(__FILE__) . '/SassElementsList.class.php';

/**
 * Sass element.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass
 */
class SassElement extends CommonElement
{
	/**
	 * The constructor
	 *
	 * @param SassElement Parent element
	 * @param string Element rule
	 * @param array Array of attributes
	 * @param SassElementsList Children
	 */
	public function __construct($parent = null, $rule, Array $attributes = array(), $children = null)
	{
		if ($parent instanceof SassElement)
			$this->setParent($parent);
		$this->setRule($rule);
		$this->setAttributes($attributes);
		if ($children instanceof SassElementsList)
			$this->setChildren($children);
		else
			$this->setChildren(new SassElementsList());
	}
	
	/**
	 * Element rule
	 *
	 * @var string
	 */
	protected $rule;

	/**
	 * Get the rule. Element rule
	 *
	 * @return string
	 */
	public function getRule()
	{
		$rule = $this->rule;
		$rules = explode(',', $this->rule);
		$rules = array_map('trim', $rules);
		foreach ($rules as $key => $value)
			$rules[$key] = ($this->hasParent() ? implode(" $value, ", array_map('trim', explode(',', $this->getParent()->getRule($value)))) . " $value" : $value);
		$rule = implode(', ', $rules);
		return $rule;
	}

	/**
	 * Set the rule. Element rule
	 *
	 * @param string Rule data
	 * @return object
	 */
	public function setRule($rule)
	{
		$this->rule = $rule;
		return $this;
	}

	/**
	 * List of attributes
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Get the attributes. List of attributes
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Set the attributes. List of attributes
	 *
	 * @param array Attributes data
	 * @return object
	 */
	public function setAttributes(Array $attributes)
	{
		$this->attributes = $attributes;
		return $this;
	}

	/**
	 * Set element attribute
	 *
	 * @param string Name
	 * @param string Value
	 * @return object
	 */
	public function setAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
		return $this;
	}

	/**
	 * Remove attribute
	 *
	 * @param string Name
	 * @return object
	 */
	public function removeAttribute($name)
	{
		unset($this->attributes[$name]);
		return $this;
	}
}

?>