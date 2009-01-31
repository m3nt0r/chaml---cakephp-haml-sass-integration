<?php
/**
 * Nested Sass renderer.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass
 */

require_once dirname(__FILE__) . '/../SassRenderer.class.php';

/**
 * Nested Sass renderer.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass
 */
class NestedSassRenderer extends SassRenderer
{
	/**
	 * Indention level
	 *
	 * @var integer
	 */
	protected static $level = -1;

	/**
	 * Render Sass
	 *
	 * @return string
	 */
	public function render()
	{
		self::$level++;
		$result = '';
		foreach ($this->getElements() as $element)
		{
			if (count($element->getAttributes()) > 0)
			{
				$result .= str_repeat(' ', self::$level*2).$element->getRule()." {\n";
				foreach ($element->getAttributes() as $name => $value)
					$result .= str_repeat(' ', self::$level*2+2) . "$name: $value;\n";
				$result = rtrim($result);
				$result .= " }\n";
			}
			else
				self::$level--;
			$result .= new self($element->getChildren());
		}
		self::$level--;
		return $result;
	}
}

?>