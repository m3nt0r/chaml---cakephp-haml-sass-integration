<?php
/**
 * Length Sass calculator.
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
 * Length Sass calculator.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass.Calculator
 */
class LengthSassCalculator implements SassCalculator
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
		if ($ret->sameUnit($other))
		{
			list($a, $u) = $this->extract($this->value);
			list($b, $u2) = $this->extract($other, $u);
			if (($u2 == '%' || $u == '%') && $u != $u2)
				$ret->value = ($a+$a*$b/100) . $u;
			else
				$ret->value = ($a+$b) . $u;
		}
		else
			$ret->value .= ' '.SassParser::TOKEN_ADDITION." $other";
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
		if ($ret->sameUnit($other))
		{
			list($a, $u) = $this->extract($this->value);
			list($b) = $this->extract($other, $u);
			if (($u2 == '%' || $u == '%') && $u != $u2)
				$ret->value = ($a-$a*$b/100) . $u;
			else
				$ret->value = ($a-$b) . $u;
		}
		else
			$ret->value .= ' '.SassParser::TOKEN_SUBTRACTION." $other";
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
		if ($ret->sameUnit($other))
		{
			list($a, $u) = $this->extract($this->value);
			list($b) = $this->extract($other, $u);
			$ret->value = ($a*$b) . $u;
		}
		else
			$ret->value .= ' '.SassParser::TOKEN_MULTIPLICATION." $other";
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
		if ($ret->sameUnit($other))
		{
			list($a, $u) = $this->extract($this->value);
			list($b) = $this->extract($other, $u);
			$ret->value = ($a/$b) . $u;
		}
		else
			$ret->value .= ' '.SassParser::TOKEN_DIVISION." $other";
		return $ret;
	}

	/**
	 * Check for same unit
	 *
	 * @param string Other
	 * @return boolean
	 */
	public function sameUnit($other)
	{
		if ($other instanceof SassCalculator)
			$other = $other->get();
		preg_match('/[0-9.]+('.implode('|', self::$units).')/i', $other, $o);
		preg_match('/[0-9.]+('.implode('|', self::$units).')/i', $this->value, $v);
		if (!isset($o[1]))
			$o[1] = $v[1];
		if (!isset($v[1]))
			$v[1] = $o[1];
		return $o[1] == $v[1] || $o[1] == '%' || $o[2] == '%';
	}

	/**
	 * Extract value and unit
	 *
	 * @param string Other
	 * @param string Defualt unit
	 * @return array
	 */
	public function extract($other, $default = 'px')
	{
		if ($other instanceof SassCalculator)
			$other = $other->get();
		preg_match('/([0-9.]+)('.implode('|', self::$units).')?/i', $other, $o);
		if (!isset($o[2]))
			$o[2] = $default;
		return array_slice($o, 1);
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

	/**
	 * List of CSS units
	 *
	 * @var array
	 */
	protected static $units = array('px', 'em', 'ex', 'pt', 'in', 'mm', 'cm', '%');
}

?>