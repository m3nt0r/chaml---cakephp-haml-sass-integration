<?php
/**
 * Color Sass calculator.
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
 * Color Sass calculator.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass.Calculator
 */
class ColorSassCalculator implements SassCalculator
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
		list($r, $g, $b) = self::extractRGB($this->value);
		list($ro, $go, $bo) = self::extractRGB($other);
		$ret->value = self::buildRGB($r + $ro, $g + $go, $b + $bo);
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
		list($r, $g, $b) = self::extractRGB($this->value);
		list($ro, $go, $bo) = self::extractRGB($other);
		$ret->value = self::buildRGB($r - $ro, $g - $go, $b - $bo);
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
		list($r, $g, $b) = self::extractRGB($this->value);
		list($ro, $go, $bo) = self::extractRGB($other);
		$ret->value = self::buildRGB($r * $ro, $g * $go, $b * $bo);
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
		list($r, $g, $b) = self::extractRGB($this->value);
		list($ro, $go, $bo) = self::extractRGB($other);
		$ret->value = self::buildRGB($r / $ro, $g / $go, $b / $bo);
		return $ret;
	}

	/**
	 * Create rgb()
	 *
	 * @param integer R
	 * @param integer G
	 * @param integer B
	 * @return string
	 */
	public static function buildRGB($r, $g, $b)
	{
		$r = round(abs($r));
		$g = round(abs($g));
		$b = round(abs($b));
		$r = $r > 255 ? $r % 255 : $r;
		$g = $g > 255 ? $g % 255 : $g;
		$b = $b > 255 ? $b % 255 : $b;
		return "rgb($r, $g, $b)";
	}

	/**
	 * Extract array of RGB from rgb()
	 *
	 * @param mixed
	 * @return array
	 */
	public static function extractRGB($s)
	{
		if (is_numeric($s))
			return array($s, $s, $s);
		preg_match('/rgb\(([0-9]{1,3}),[ ]?([0-9]{1,3}),[ ]?([0-9]{1,3})\)/i', $s, $m);
		return array_slice($m, 1);
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