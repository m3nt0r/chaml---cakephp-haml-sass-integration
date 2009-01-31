<?php
/**
 * Sass calculator.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass.Calculator
 */

require_once dirname(__FILE__) . '/SassCalculatorException.class.php';
require_once dirname(__FILE__) . '/calculators/LengthSassCalculator.class.php';
require_once dirname(__FILE__) . '/calculators/ColorSassCalculator.class.php';
require_once dirname(__FILE__) . '/calculators/NumberSassCalculator.class.php';
require_once dirname(__FILE__) . '/calculators/StringSassCalculator.class.php';

/**
 * Sass calculator.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass.Calculator
 */
interface SassCalculator
{
	/**
	 * The constructor. Create value
	 *
	 * @param string Value
	 */
	public function __construct($value);

	/**
	 * Add $other to self. Don't modify $value
	 *
	 * @param mixed Value
	 * @return SassCalculator
	 */
	public function add($other);

	/**
	 * Execute substraction.
	 *
	 * @param mixed Value
	 * @return SassCalculator
	 */
	public function sub($other);

	/**
	 * Multiply self $other-times.
	 *
	 * @param mixed Value
	 * @return SassCalculator
	 */
	public function mul($other);

	/**
	 * Execute division
	 *
	 * @param mixed Value
	 * @return SassCalculator
	 */
	public function div($other);

	/**
	 * Return value
	 *
	 * @return mixed
	 */
	public function get();
}

?>