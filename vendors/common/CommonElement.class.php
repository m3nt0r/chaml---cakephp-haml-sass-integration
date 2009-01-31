<?php
/**
 * Common element.
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
 * Common element.
 *
 * @link http://haml.hamptoncatlin.com/ Original Common parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Common
 */
abstract class CommonElement
{
	/**
	 * The constructor
	 *
	 * @param CommonElement Parent element
	 * @param CommonElementsList Children
	 */
	abstract public function __construct($parent = null, $children = null);

	/**
	 * Parent of element
	 *
	 * @var CommonElement
	 */
	protected $parent;

	/**
	 * Get the parent. Parent of element
	 *
	 * @return CommonElement
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Set the parent. Parent of element
	 *
	 * @param CommonElement Parent data
	 * @return object
	 */
	public function setParent(CommonElement $parent)
	{
		$this->parent = $parent;
		$this->parent->addChild($this);
		return $this;
	}

	/**
	 * Check for parent
	 *
	 * @return boolean
	 */
	public function hasParent()
	{
		return $this->parent instanceof CommonElement;
	}

	/**
	 * Return grandpa (parent of parent)
	 *
	 * @throws Exception Throwed if element don't have grandpa
	 * @return CommonElement
	 */
	public function getGrandpa()
	{
		if ($this->hasGrandpa())
			return $this->getParent()->getParent();
		else
			throw new Exception('Element\'s parent don\'t have parent');
	}

	/**
	 * Check for grandpa (parent of parent)
	 *
	 * @return boolean
	 */
	public function hasGrandpa()
	{
		return ($this->hasParent()) ? $this->getParent()->hasParent() : false;
	}

	/**
	 * List of children
	 *
	 * @var CommonElementsList
	 */
	protected $children;

	/**
	 * Add child
	 *
	 * @param CommonElement Child
	 * @return object
	 */
	public function addChild(CommonElement $child)
	{
		$this->getChildren()->append($child);
		return $this;
	}

	/**
	 * Get the children. List of children
	 *
	 * @return CommonElementsList
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * Set the children. List of children
	 *
	 * @param CommonElementsList Children data
	 * @return object
	 */
	public function setChildren(CommonElementsList $children)
	{
		$this->children = $children;
		return $this;
	}

	/**
	 * Check for children
	 *
	 * @return boolean
	 */
	public function hasChildren()
	{
		return $this->children->count() > 0;
	}

	/**
	 * Count element's children
	 *
	 * @return integer
	 */
	public function countChildren()
	{
		return $this->children->count();
	}
	
	/**
	 * Kill (remove) children
	 *
	 * @return object
	 */
	public function killChildren()
	{
		$this->getChildren()->exchangeArray(array());
		return $this;
	}
	
	/**
	 * Check for element siblings
	 *
	 * @return boolean
	 */
	public function hasSiblings()
	{
		if ($this->hasParent())
			return count($this->getParent()->getChildren()) > 1;
		else
			return false;
	}
	
	/**
	 * Return element siblings
	 *
	 * @return CommonElementsList
	 */
	public function getSiblings()
	{
		if ($this->hasParent())
			return array_diff($this->getParent()->getChildren(), array($this));
		else
			return array();
	}
	
	/**
	 * Count child number
	 *
	 * @return integer
	 */
	public function countChildNumber()
	{
		if ($this->hasSiblings())
			return array_search($this, $this->getParent()->getChildren(), true);
		else
			return 0;
	}
	
	/**
	 * Is this child first child of parent
	 *
	 * @return boolean
	 */
	public function isFirstChild()
	{
		return $this->countChildNumber() == 0;
	}
	
	/**
	 * Is this child last child of parent
	 *
	 * @return boolean
	 */
	public function isLastChild()
	{
		if ($this->hasParent())
			return $this->countChildNumber() == ($this->getParent()->countChildren() - 1);
		else
			return false;
	}
}

?>