<?php
/**
 * Number Sass calculator.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass.Calculator
 */

require_once dirname(__FILE__) . '/../SassCalculator.interface.php';

/**
 * Number Sass calculator.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass.Calculator
 */
class NumberSassCalculator implements SassCalculator
{
	/**
	 * Calculator value
	 *
	 * @var string
	 */
	public $value;

	/**
	 * The constructor. Create value
	 *
	 * @param string Value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * Add $other to self. Don't modify $value
	 *
	 * @param mixed Value
	 * @return SassCalculator
	 */
	public function add($other)
	{
		if ($other instanceof SassCalculator)
			$other = $other->get();
		$ret = clone $this;
		$ret->value = round($ret->value + $other, 2);
		return $ret;
	}

	/**
	 * Execute substraction.
	 *
	 * @param mixed Value
	 * @return SassCalculator
	 */
	public function sub($other)
	{
		if ($other instanceof SassCalculator)
			$other = $other->get();
		$ret = clone $this;
		$ret->value = round($ret->value - $other, 2);
		return $ret;
	}

	/**
	 * Multiply self $other-times.
	 *
	 * @param mixed Value
	 * @return SassCalculator
	 */
	public function mul($other)
	{
		if ($other instanceof SassCalculator)
			$other = $other->get();
		$ret = clone $this;
		$ret->value = round($ret->value * $other, 2);
		return $ret;
	}

	/**
	 * Execute division
	 *
	 * @param mixed Value
	 * @return SassCalculator
	 */
	public function div($other)
	{
		if ($other instanceof SassCalculator)
			$other = $other->get();
		$ret = clone $this;
		$ret->value = round($ret->value / $other, 2);
		return $ret;
	}

	/**
	 * Return value
	 *
	 * @return mixed
	 */
	public function get()
	{
		return $this->value;
	}
}

?>