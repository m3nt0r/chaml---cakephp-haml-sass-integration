<?php
/**
 * Sass parser.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass
 */

require_once dirname(__FILE__) . '/../common/CommonCache.class.php';
require_once dirname(__FILE__) . '/../common/CommonParser.class.php';
require_once dirname(__FILE__) . '/SassException.class.php';
require_once dirname(__FILE__) . '/SassCalculator.interface.php';
require_once dirname(__FILE__) . '/SassElement.class.php';
require_once dirname(__FILE__) . '/SassElementsList.class.php';
require_once dirname(__FILE__) . '/SassRenderer.class.php';

/**
 * Sass parser.
 *
 * @link http://haml.hamptoncatlin.com/ Original Sass parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Sass
 */
class SassParser extends CommonParser
{
	/**
	 * Instance of SassParser
	 *
	 * @var SassParser
	 */
	protected static $instance;

	/**
	 * Parse Sass code
	 *
	 * @param string Source
	 * @param array Array of constants
	 * @return string
	 */
	public static function sass($source, Array $constants = array())
	{
		if (!(self::$instance instanceof SassParser))
			self::$instance = new SassParser();
		return self::$instance->setSource($source)->render();
	}

	/**
	 * The constructor
	 *
	 * @param string Path
	 * @param string Temporary directory
	 * @param string SassRenderer type
	 */
	public function __construct($path = null, $tmp = null, $type = null)
	{
		if (!is_null($path))
			$this->setPath($path);
		if (is_string($tmp))
			$this->setTmp($tmp); else
		if (!is_null($path))
			$this->setTmp($path);
		else
			$this->setTmp(ini_get('session.save_path'));
		if (!is_null($type))
			$this->setRenderer($type);
		$this->setElements(new SassElementsList());
	}

	/**
	 * Render Sass code
	 *
	 * @param string Filename
	 * @param string SassRenderer type
	 * @return string
	 */
	public function render($file = null, $type = null)
	{
		if (!is_null($file))
			$this->setFile($file);
		if (!is_null($type))
			$this->setRenderer($type);
		$cache = new CommonCache($this->getTmp(), 'css', $this->getSource());

		if (!$cache->isCached())
		{
			// BEGIN: Flat to tree structure
			$lines = explode(self::TOKEN_LINE, $this->getSource());
			$last = array(-1 => null);
			$attributes = array();
			$attributeLevel = 0;
			$namespace = null;
			$namespaceLevel = 0;
			foreach ($lines as $line)
			{
				$stop = true;
				$cleaned = $this->cleanLine($line);
				$level = $this->countLevel($line);
				// Check for constant definition
				if (preg_match('/^'.preg_quote(self::TOKEN_CONSTANT).'(.+?)[ ]?'.preg_quote(self::TOKEN_CONSTANT_VALUE).'[ ]?["]?(.+)["]?/', $cleaned, $matches))
					$this->setConstant($matches[1], $matches[2]); else
				// Check for dynamic attribute definition
				if (preg_match('/^'.preg_quote(self::TOKEN_ATTRIBUTE).'(.+?)[ ]?'.preg_quote(self::TOKEN_ATTRIBUTE_CALCULATE ).'[ ]?(.+)/', $cleaned, $matches))
				{
					if ($namespaceLevel+1 != $level)
						$namespace = null;
					$attributes[empty($namespace) ? $matches[1] : "$namespace-{$matches[1]}"] = $this->calculate($matches[2]);
					$attributeLevel = $level - (empty($namespace) ? 1 : 2);
				} else
				// Check for attribute definition
				if (preg_match('/^'.preg_quote(self::TOKEN_ATTRIBUTE).'(.+?) (.+)/', $cleaned, $matches))
				{
					if ($namespaceLevel+1 != $level)
						$namespace = null;
					$attributes[empty($namespace) ? $matches[1] : "$namespace-{$matches[1]}"] = $matches[2];
					$attributeLevel = $level - (empty($namespace) ? 1 : 2);
				} else
				// Check for attribute namespace
				if (preg_match('/^'.preg_quote(self::TOKEN_ATTRIBUTE_NAMESPACE).'(.+)/', $cleaned, $matches))
				{
					$namespace = $matches[1];
					$namespaceLevel = $level;
				} else
				// Check for comment
				if (preg_match('|^'.preg_quote(self::TOKEN_COMMENT).'|', $cleaned))
					$stop = true;
				// Nothing special
				else
				{
					$stop = false;
					$namespace = null;
				}
				// Remove blank lines
				if (empty($cleaned) || $stop)
					continue;
				// Assign attributes
				if (!empty($attributes) && $last[$attributeLevel] instanceof SassElement)
				{
					foreach ($attributes as $name => $value)
						$last[$attributeLevel]->setAttribute($name, $value);
					$attributeLevel = 0;
					$attributes = array();
				}
				// Get parent element
				$parent = $last[$level - 1];
				// Create new element
				$element = new SassElement($parent, $cleaned);
				// Mark as parent
				$last[$level] = $element;
				if ($level == 0)
					$this->addElement($element);
			}
			// Assign attributes
			if (!empty($attributes) && $last[$attributeLevel] instanceof SassElement)
			{
				foreach ($attributes as $name => $value)
					$last[$attributeLevel]->setAttribute($name, $value);
				$attributeLevel = 0;
				$attributes = array();
			}
			// END: Flat to tree structure
			// Render to CSS
			return $cache->setCached(SassRenderer::getInstance($this->getElements(), $this->getRenderer())->render())->cacheIt()->getCached();
		}
		else
			return $cache->getCached();
	}

	/**
	 * List of constants assigned to parser
	 *
	 * @var array
	 */
	protected $constants;

	/**
	 * Get the constants. List of constants assigned to parser
	 *
	 * @return array
	 */
	public function getConstants()
	{
		return $this->constants;
	}

	/**
	 * Set the constants. List of constants assigned to parser
	 *
	 * @param array Constants data
	 * @return object
	 */
	public function setConstants($constants)
	{
		$this->constants = $constants;
		return $this;
	}

	/**
	 * Set constant
	 *
	 * @param string Name
	 * @param mixed Data
	 * @return object
	 */
	public function setConstant($name, $value)
	{
		$this->constants[$name] = $value;
		return $this;
	}

	/**
	 * Calculate attribute data
	 *
	 * @param string Expression
	 * @return string Result
	 */
	public function calculate($expression)
	{
		// Replace constants with values
		foreach ($this->getConstants() as $name => $value)
			$expression = str_ireplace(self::TOKEN_CONSTANT.$name, $value, $expression);
		// Replace colors names with RGB codes
		foreach (self::$colors as $name => $rgb)
			$expression = str_ireplace($name, 'rgb('.implode(', ', $rgb).')', $expression);
		// Replace colors representation in HEX to rgb()
		$expression = preg_replace_callback('/#(['.self::HEX.']{6})|#(['.self::HEX.']{3})/i', array($this, 'hex2rgb'), $expression);
		$right_left = $last_left = 0;
		$rgb = $is_string = false;
		for ($i = 0; $i < strlen($expression); $i++)
		{
			$token = $expression{$i};
			if ($token == self::TOKEN_STRING && $is_string)
				$is_string = false; else
			if ($token == self::TOKEN_STRING)
				$is_string = true; else
			if ($token == self::TOKEN_LEFT_BRACKET)
			{
				$last_left = $i;
				$rgb = substr($expression, $i-3, 3) == 'rgb';
			} else
			if ($token == self::TOKEN_RIGHT_BRACKET)
			{
				if ($rgb)
					$rgb = false;
				else
				{
					$last_right = $i;
					$expression = str_replace('+-', '-', str_replace('--', '+', str_replace(substr($expression, $last_left, $last_right - $last_left + 1), $this->rcalc(substr($expression, $last_left+1, $last_right - $last_left-1)), $expression)));
					$i = 0;
				}
			}
		}
		$expression = $is_string ? trim($expression, self::TOKEN_STRING) : $this->rcalc($expression);
		return $expression;
	}

	/**
	 * Execute opearation on numbers, colors, strings
	 *
	 * @param mixed Value
	 * @param mixed Other
	 * @param mixed Operation (add, sub, mul, div)
	 * @return string
	 */
	public function calc_call($value, $other, $operation)
	{
		$value = $this->rcalc($value);
		$other = $this->rcalc($other);
		if (preg_match('/^[-0-9.]+$/', $value))
			$v = new NumberSassCalculator(trim($value)); else
		if (preg_match('/^rgb\((.+?)\)$/i', $value))
			$v = new ColorSassCalculator($value); else
		if (preg_match('/([-0-9.]+)([a-z]{1,2})/i', $value))
			$v = new LengthSassCalculator($value);
		else
			$v = new StringSassCalculator($value);
		return str_replace('+-', '-', str_replace('--', '+', call_user_func(array($v, $operation), $other)->get()));;
	}

	/**
	 * Simple calculator (without parenthesis)
	 *
	 * @param string Expression
	 * @return string
	 */
	public function rcalc($e)
	{
		$e = str_replace('+-', '-', str_replace('--', '+', trim($e)));
		$operands = self::TOKEN_ADDITION.'\\'.self::TOKEN_SUBTRACTION.self::TOKEN_MULTIPLICATION.self::TOKEN_DIVISION;
		$e = preg_replace("#\s*([$operands])\s*#", '$1', $e);
		if (preg_match("#(.+?)\+(.+)#", $e, $m))
			$e = $this->calc_call($m[1], $m[2], 'add');
		if (preg_match("#([^$operands]+?)-(.+)#", $e, $m))
			$e = $this->calc_call($m[1], $m[2], 'sub');
		if (preg_match("#(.+?)\*(.+)#", $e, $m))
			$e = $this->calc_call($m[1], $m[2], 'mul');
		if (preg_match("#(.+?)/(.+)#", $e, $m))
			$e = $this->calc_call($m[1], $m[2], 'div');
		return $e;
	}

	/**
	 * Replace HEX color representation
	 * to CSS rgb() function
	 *
	 * @param string HEX color (3 or 6 letters)
	 * @return string
	 */
	public function hex2rgb($m)
	{
		$hex = $hex2 = is_array($m) ? $m[1] : $m;
		if (strlen($hex2) == 3)
			for ($i = 0; $i < 3; $i++)
				$hex{$i} = $hex{$i+1} = $hex2{$i};
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
		$rgb = "rgb($r, $g, $b)";
		return $rgb;
	}

	/**
	 * Hex characters
	 *
	 * @var string
	 */
	const HEX = '0123456789ABCDEF';

	/**
	 * RGB representation of colors by names
	 *
	 * @var array
	 */
	public static $colors = array
	(
	//	Name				Red		Green	Blue
		'aqua'		=> array(0,		255,	255),
		'black'		=> array(0,		0,		0),
		'blue'		=> array(0,		0,		255),
		'fuchsia'	=> array(255,	0,		255),
		'gray'		=> array(128,	128,	128),
		'green'		=> array(0,		128,	0),
		'lime'		=> array(0,		255,	0),
		'maroon'	=> array(128,	0,		0),
		'navy'		=> array(0,		0,		128),
		'olive'		=> array(128,	128,	0),
		'purple'	=> array(128,	0,		128),
		'red'		=> array(255,	0,		0),
		'silver'	=> array(192,	192,	192),
		'teal'		=> array(0,		128,	128),
		'white'		=> array(255,	255,	255),
		'yellow'	=> array(255,	255,	0)
	);

	const TOKEN_LINE = "\n";
	const TOKEN_COMMENT = '/';
	const TOKEN_ATTRIBUTE = ':';
	const TOKEN_ATTRIBUTE_NAMESPACE = ':';
	const TOKEN_CONSTANT = '!';
	const TOKEN_CONSTANT_VALUE = '=';
	const TOKEN_ATTRIBUTE_CALCULATE = '=';
	const TOKEN_MULTIPLICATION = '*';
	const TOKEN_DIVISION = '/';
	const TOKEN_ADDITION = '+';
	const TOKEN_SUBTRACTION = '-';
	const TOKEN_LEFT_BRACKET = '(';
	const TOKEN_RIGHT_BRACKET = ')';
	const TOKEN_STRING = '"';
}

?>