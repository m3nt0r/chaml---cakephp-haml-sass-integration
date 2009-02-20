<?php
/**
 * Haml parser.
 *
 * @link http://haml.hamptoncatlin.com/ Original Haml parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Haml
 */

require_once dirname(__FILE__) . '/../common/CommonCache.class.php';

/**
 * Haml parser.
 *
 * Haml is templating language. It is very simple and clean.
 * Example Haml code
 * <code>
 * !!! 1.1
 * %html
 *   %head
 *     %title= $title ? $title : 'none'
 *     %link{ :rel => 'stylesheet', :type => 'text/css', :href => "$uri/tpl/$theme.css" }
 *   %body
 *     #header
 *       %h1.sitename example.com
 *     #content
 *       / Table with models
 *       %table.config.list
 *         %tr
 *           %th ID
 *           %th Name
 *           %th Value
 *         - foreach ($models as $model)
 *           %tr[$model]
 *             %td= $model->ID
 *             %td= $model->name
 *             %td= $model->value
 *     #footer
 *       %address.author Random Hacker
 * </code>
 * Comparing to original Haml language I added:
 * <ul>
 *   <li>
 *     Support to translations - use '$'
 * <code>
 * %strong$ Log in
 * </code>
 *   </li>
 *   <li>
 *     Including support ('!!') and level changing ('?')
 * <code>
 * !! html
 * !! page.header?2
 * %p?3
 *   Foo bar
 * !! page.footer?2
 * </code>
 *   </li>
 * </ul>
 *
 * @link http://haml.hamptoncatlin.com/ Original Haml parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Haml
 */
class HamlParser
{
	/**
	 * Haml source
	 *
	 * @var string
	 */
	public $sSource = '';

	/**
	 * Files path
	 *
	 * @var string
	 */
	protected $sPath = '';

	/**
	 * Compile templates??
	 *
	 * @var boolean
	 */
	protected $bCompile = true;

	/**
	 * Filename
	 *
	 * @var string
	 */
	protected $sFile = '';

	/**
	 * Parent parser
	 *
	 * @var object
	 */
	protected $oParent = null;

	/**
	 * Children parsers
	 *
	 * @var array
	 */
	protected $aChildren = array();

	/**
	 * Indent level
	 *
	 * @var integer
	 */
	public $iIndent = -1;

	/**
	 * Translation function name.
	 *
	 * @var string
	 */
	public $sTranslate = '__';

	/**
	 * Translation function
	 *
	 * You can use it in templates.
	 * <code>
	 * %strong= $foo.$this->translate('to translate')
	 * </code>
	 */
	protected function translate()
	{
		$a = func_get_args();
		return call_user_func_array($this->sTranslate, $a);
	}

	/**
	 * Temporary directory
	 *
	 * @var string
	 */
	protected $sTmp = '';

	/**
	 * Block of PHP code
	 *
	 * @var boolean
	 */
	protected $bBlock = false;

	/**
	 * Current tag name
	 *
	 * @var string
	 */
	public $sTag = 'div';

	/**
	 * The constructor.
	 *
	 * Create Haml parser. Second argument can be path to
	 * temporary directory or boolean if true then templates
	 * are compiled to templates path else if false then
	 * templates are compiled on every run
	 * <code>
	 * <?php
	 * require_once './includes/haml/HamlParser.class.php';
	 *
	 * $parser = new HamlParser('./tpl', './tmp');
	 * $foo = 'bar';
	 * $parser->assign('foo', $foo);
	 * $parser->display('mainpage.haml');
	 * ?>
	 * </code>
	 *
	 * @param string Path to files
	 * @param boolean/string Compile templates (can be path)
	 * @param object Parent parser
	 * @param array Array with debug informations
	 * @param boolean Is used dynamic including
	 */
	public function __construct($sPath = false, $bCompile = true, $oParent = null, $aDebug = null, $bInside = false)
	{
		if ($sPath)
			$this->setPath($sPath);
		$this->bCompile = $bCompile;
		if (is_string($bCompile))
			$this->setTmp($bCompile); else
		if ($sPath)
			$this->setTmp($sPath);
		else
			$this->setTmp(ini_get('session.save_path'));
		if ($oParent)
		{
			$this->setParent($oParent);
			if ($aDebug)
				$this->aDebug = $aDebug;
		}
		$this->bInside = $bInside;
		if (!self::$otc)
			self::__otc();
	}

	/**
	 * Is used dynamic including
	 *
	 * @var boolean
	 */
	protected $bInside = false;

	/**
	 * Debugging informations
	 *
	 * @var array
	 */
	public $aDebug = null;

	/**
	 * Render Haml. Append globals variables
	 *
	 * Simple way to use Haml
	 * <code>
	 * echo HamlParser::haml('%strong Hello, World!'); // <strong>Hello, World!</strong>
	 * $foo = 'bar'; // This is global variable
	 * echo Haml::haml('%strong= "Foo is $foo"'); // <strong>Foo is bar</strong>
	 * </code>
	 *
	 * @param string Haml source
	 * @return string xHTML
	 */
	public static function haml($sSource)
	{
		static $__haml_parser;
		if (!$__haml_parser)
			$__haml_parser = new HamlParser();
		$__haml_parser->setSource($sSource);
		$__haml_parser->append($GLOBALS);
		return $__haml_parser->fetch();
	}

	/**
	 * One time constructor, is executed??
	 *
	 * @var boolean
	 */
	protected static $otc = false;

	/**
	 * One time constructor. Register Textile block
	 * if exists Textile class and Markdown block if
	 * exists Markdown functions
	 */
	protected static function __otc()
	{
		self::$otc = true;
		if (class_exists('Textile', false))
			self::registerBlock(array(new Textile, 'TextileThis'), 'textile');
		if (function_exists('Markdown'))
			self::registerBlock('Markdown', 'markdown');
		self::tryRegisterSass();
	}

	/**
	 * Try register Sass engine as text block
	 *
	 * @return boolean
	 */
	protected static function tryRegisterSass()
	{
		if (file_exists($f = dirname(__FILE__) . '/../sass/SassParser.class.php'))
			require_once $f;
		else
			return false;
		self::registerBlock(array('SassParser', 'sass'), 'sass');
		return true;
	}

	/**
	 * Set parent parser. Used internally.
	 *
	 * @param object Parent parser
	 * @return object
	 */
	public function setParent(HamlParser $oParent)
	{
		$this->oParent = $oParent;
		$this->bRemoveBlank = $oParent->bRemoveBlank;
		return $this;
	}

	/**
	 * Set files path.
	 *
	 * @param string File path
	 * @return object
	 */
	public function setPath($sPath)
	{
		$this->sPath = realpath($sPath);
		return $this;
	}

	/**
	 * Set filename.
	 *
	 * Filename can be full path to file or
	 * filename (then file is searched in path)
	 * <code>
	 * // We have file ./foo.haml and ./tpl/bar.haml
	 * // ...
	 * $parser->setPath('./tpl');
	 * $parser->setFile('foo.haml'); // Setted to ./foo.haml
	 * $parser->setFile('bar.haml'); // Setted to ./tpl/bar.haml
	 * </code>
	 *
	 * @param string Filename
	 * @return object
	 */
	public function setFile($sPath)
	{
		if (file_exists($sPath))
			$this->sFile = $sPath;
		else
			$this->sFile = "{$this->sPath}/$sPath";
		$this->setSource(file_get_contents($this->sFile));
		return $this;
	}

	/**
	 * Return filename to include
	 *
	 * You can override this function.
	 *
	 * @param string Name
	 * @return string
	 */
	public function getFilename($sName)
	{
		return "{$this->sPath}/".trim($sName).'.haml';
	}

	/**
	 * Real source
	 *
	 * @var string
	 */
	public $sRealSource = '';

	/**
	 * Set source code
	 *
	 * <code>
	 * // ...
	 * $parser->setFile('foo.haml');
	 * echo $parser->setSource('%strong Foo')->fetch(); // <strong>Foo</strong>
	 * </code>
	 *
	 * @param string Source
	 * @return object
	 */
	public function setSource($sHaml)
	{
		$this->sSource = trim($sHaml, self::TOKEN_INDENT);
		$this->sRealSource = $sHaml;
		$this->sTag = null;
		$this->aChildren = array();
		return $this;
	}


	/**
	 * Set temporary directory
	 *
	 * @param string Directory
	 * @return object
	 */
	public function setTmp($sTmp)
	{
		$this->sTmp = realpath($sTmp);
		return $this;
	}

	/**
	 * Debug mode
	 *
	 * @see HamlParser::isDebug()
	 * @var boolean
	 */
	protected static $bDebug = false;

	/**
	 * Set and check debug mode. If is set
	 * debugging mode to generated source are
	 * added comments with debugging mode and
	 * Haml source is not cached.
	 *
	 * @param boolean Debugging mode (if null, then only return current state)
	 * @return boolean
	 */
	public function isDebug($bDebug = null)
	{
		if (!is_null($bDebug))
			self::$bDebug = $bDebug;
		if (self::$bDebug)
			$this->bCompile = false;
		return self::$bDebug;
	}

	public $compress = false;
	public $showCacheTime = false;
	public $noCache = false;
	
	/**
	 * Render the source or file
	 *
	 * @see HamlParser::fetch()
	 * @return string
	 */
	public function render()
	{
		$__aSource = explode(self::TOKEN_LINE, $this->sRealSource = $this->sSource = $this->parseBreak($this->sSource));
		$__sCompiled = '';
		
		if ($this->oParent instanceof HamlParser) {
			$__sCompiled = $this->parseLine($__aSource[0]);
		}
		else
		{	
			// pretty up cache path
			$filePath = explode(DS, $this->sFile);
			$fileName = $filePath[count($filePath)-1];
			$compFileName = r('.haml', '', $fileName);
			$viewPath = r($fileName, '', r(ROOT.DS.APP_DIR, '', $this->sFile));
			$tempFileName = $compFileName.'.ctp';
			$tempRoot = $this->sTmp;
			$tempPath = $tempRoot . $viewPath;
			$tempFile = $tempPath . $tempFileName;
			
			// Caching
			if (!$this->noCache) 
			{
				if (file_exists($tempFile)) {
					$tempTime = filemtime($tempFile);			
					if (filemtime($this->sFile) < $tempTime) {
						// Expand compiled template
						// set up variables for context
						foreach (self::$aVariables as $__sName => $__mValue)
							$$__sName = $__mValue;

						ob_start();
							require $tempFile;
						$__c = rtrim(ob_get_clean()); // capture the result, and discard ob

						// Strip obsolete whitespace
						if ($this->compress) $__c = preg_replace('/(?:(?<=\>)|(?<=\/\)))(\s+)(?=\<\/?)/', '', $__c);
					
						return $__c . ($this->showCacheTime ? '<!-- cache '.date('d.m.y H:i:s', $tempTime).' -->' : '');
					}
					unlink($tempFile);
				}
			}
			
			$this->aChildren = array();
			$__sGenSource = $this->sSource;
			do
			{
				$__iIndent = 0;
				$__iIndentLevel = 0;
				foreach ($__aSource as $__iKey => $__sLine)
				{
					$__iLevel = $this->countLevel($__sLine);
					if ($__iLevel <= $__iIndentLevel)
						$__iIndent = $__iIndentLevel = 0;
										
					if (preg_match('/\\'.self::TOKEN_LEVEL.'([0-9]+)$/', $__sLine, $__aMatches))
					{
						$__iIndent = (int)$__aMatches[1];
						$__iIndentLevel = $__iLevel;
						$__sLine = preg_replace('/\\'.self::TOKEN_LEVEL."$__iIndent$/", '', $__sLine);
					}
					$__sLine = str_repeat(self::TOKEN_INDENT, $__iIndent * self::INDENT) . $__sLine;
					$__aSource[$__iKey] = $__sLine;
					if (preg_match('/^(\s*)'.self::TOKEN_INCLUDE.' (.+)/', $__sLine, $aMatches))
					{
						$__sISource = file_get_contents($__sIFile = $this->getFilename($aMatches[2]));
						if ($this->isDebug())
							$__sISource = "// Begin file $__sIFile\n$__sISource\n// End file $__sIFile";
						$__sIncludeSource = $this->sourceIndent($__sISource, $__iIndent ? $__iIndent : $__iLevel);
						$__sLine = str_replace($aMatches[1] . self::TOKEN_INCLUDE . " {$aMatches[2]}", $__sIncludeSource, $__sLine);
						$__aSource[$__iKey] = $__sLine;
					}
					$this->sSource = implode(self::TOKEN_LINE, $__aSource);
				}
				$__aSource = explode(self::TOKEN_LINE, $this->sSource = $__sGenSource = $this->parseBreak($this->sSource));
			} while (preg_match('/(\\'.self::TOKEN_LEVEL.'[0-9]+)|(\s*[^!]'.self::TOKEN_INCLUDE.' .+)/', $this->sSource));
			$this->sSource = $this->sRealSource;

	
			$compiled = $this->parseFile($__aSource);	
		

			// Call filters
			foreach ($this->aFilters as $mFilter) $compiled = call_user_func($mFilter, $compiled);
			
			$folder = new Folder($tempPath, true);
			$file = new File($tempFile, true);
			$file->write($compiled);
			$__sCompiled = $tempFile;	
	
			// Expand compiled template
			// set up variables for context
			foreach (self::$aVariables as $__sName => $__mValue)
				$$__sName = $__mValue;
				
			ob_start();
				require $tempFile;
			$__c = rtrim(ob_get_clean()); // capture the result, and discard ob
			
			// Strip obsolete whitespace
			if ($this->compress) $__c = preg_replace('/(?:(?<=\>)|(?<=\/\)))(\s+)(?=\<\/?)/', '', $__c);
				
			// Debug Display
			if ($this->isDebug())
			{
				header('Content-Type: text/plain');
				$__a = "\nFile $this->sFile:\n";
				foreach (explode("\n", $__sGenSource) as $iKey => $sLine)
					$__a .= 'F' . ($iKey + 1) . ":\t$sLine\n";
				$__c .= rtrim($__a);
			}
			
			return $__c;
		}
		return $__sCompiled;
	}

	/**
	 * Render the source or file
	 *
	 * @see HamlParser::fetch()
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Parse multiline
	 *
	 * @param string File content
	 * @return string
	 */
	protected function parseBreak($sFile)
	{
		$sFile = preg_replace('/\\'.self::TOKEN_BREAK.'\s*/', '', $sFile);
		return $sFile;
	}

	/**
	 * Return source of child
	 *
	 * @param integer Level
	 * @return string
	 */
	public function getAsSource($iLevel)
	{
		$x = ($this->iIndent - $iLevel - 1) * self::INDENT;
		$sSource = '';
		if ($x >= 0)
			$sSource = preg_replace('|^'.str_repeat(self::TOKEN_INDENT, ($iLevel + 1) * self::INDENT).'|', '', $this->sRealSource);
		foreach ($this->aChildren as $oChild)
			$sSource .= self::TOKEN_LINE.$oChild->getAsSource($iLevel);
		return trim($sSource, self::TOKEN_LINE);
	}

	/**
	 * Create and append line to parent
	 *
	 * @param string Line
	 * @param object Parent parser
	 * @param integer Line number
	 * @return object
	 */
	public function createLine($sLine, $parent, $iLine = null)
	{
		$oHaml = new HamlParser($this->sPath, $this->bCompile, $parent, array('line'=>$iLine, 'file'=>$this->sFile));
		$oHaml->setSource(rtrim($sLine, "\r"));
		$oHaml->iIndent = $parent->iIndent + 1;
		$parent->aChildren[] = $oHaml;
		return $oHaml;
	}

	/**
	 * Parse file
	 *
	 * @param array Array of source lines
	 * @return string
	 */
	protected function parseFile($aSource)
	{
		$aLevels = array(-1 => $this);
		$sCompiled = '';
		foreach ($aSource as $iKey => $sSource)
		{
			$iLevel = $this->countLevel($sSource);
			$aLevels[$iLevel] = $this->createLine($sSource, $aLevels[$iLevel - 1], $iKey + 1);
		}
		
		foreach ($this->aChildren as $oChild)
			$sCompiled .= $oChild->render();
		
		
		
		$sCompiled = preg_replace('|<\?php \} \?>\s*?<\?php else \{ \?>|ius', '<?php } else { ?>', $sCompiled);
		return $sCompiled;
	}

	/**
	 * List of text processing blocks
	 *
	 * @var array
	 */
	protected static $aBlocks = array();

	/**
	 * Register block
	 *
	 * Text processing blocks are very usefull stuff ;)
	 * <code>
	 * // ...
	 * %code.checksum
	 * $tpl = <<<__TPL__
	 *   :md5
	 *     Count MD5 checksum of me
	 * __TPL__;
	 * HamlParser::registerBlock('md5', 'md5');
	 * $parser->display($tpl); // <code class="checksum">iejmgioemvijeejvijioj323</code>
	 * </code>
	 *
	 * @param mixed Callable
	 * @param string Name
	 */
	public static function registerBlock($mCallable, $sName = false)
	{
		if (!$sName)
			$sName = serialize($mCallable);
		self::$aBlocks[$sName] = $mCallable;
	}

	/**
	 * Unregister block
	 *
	 * @param string Name
	 */
	public static function unregisterBlock($sName)
	{
		unset(self::$aBlocks[$sName]);
	}

	/**
	 * Parse text block
	 *
	 * @param string Block name
	 * @param string Data
	 * @return string
	 */
	protected function parseTextBlock($sName, $sText)
	{
		return call_user_func(self::$aBlocks[$sName], $sText);
	}

	/**
	 * Eval embedded PHP code
	 *
	 * @see HamlParser::embedCode()
	 * @var boolean
	 */
	protected static $bEmbed = true;

	/**
	 * Eval embedded PHP code
	 *
	 * @param boolean
	 * @return boolean
	 */
	public function embedCode($bEmbed = null)
	{
		if (is_null($bEmbed))
			return self::$bEmbed;
		else
			return self::$bEmbed = $bEmbed;
	}

	/**
	 * Instance for getInstance
	 *
	 * @see HamlParser::getInstance()
	 * @var HamlParser
	 */
	protected static $oInstance = null;

	/**
	 * Implements singleton pattern
	 *
	 * @see HamlParser::__construct()
	 * @param string Path to files
	 * @param boolean/string Compile templates (can be path)
	 * @return HamlParser
	 */
	protected static function getInstance($sPath = false, $bCompile = true)
	{
		if (is_null(self::$oInstance))
			self::$oInstance = new self($sPath, $bCompile, null, null, true);
		return self::$oInstance;
	}

	/**
	 * Remove white spaces??
	 *
	 * @var boolean
	 * @access private
	 */
	public $bRemoveBlank = null;

	/**
	 * Remove white spaces
	 *
	 * @param boolean
	 * @return HamlParser
	 */
	public function removeBlank($bRemoveBlank)
	{
		$this->bRemoveBlank = $bRemoveBlank;
		return $this;
	}

	/**
	 * Parse line
	 *
	 * @param string Line
	 * @return string
	 */
	public function parseLine($sSource)
	{
		$sParsed = '';
		$sRealBegin = '';
		$sRealEnd = '';
		$sParsedBegin = '';
		$sParsedEnd = '';
		$bParse = true;
		// Dynamic including
		if (preg_match('/^'.self::TOKEN_INCLUDE.self::TOKEN_PARSE_PHP.' (.*)/', $sSource, $aMatches) && $this->embedCode())
		{
			return ($this->isDebug() ? "{$this->aDebug['line']}:\t{$aMatches[1]} == <?php var_export({$aMatches[1]}) ?>\n\n" : '') . "<?php echo \$this->indent(self::getInstance(\$this->sPath, \$this->sTmp)->fetch(\$this->getFilename({$aMatches[1]})), $this->iIndent, true, false); ?>";
		} else
		// Doctype parsing
		if (preg_match('/^'.self::TOKEN_DOCTYPE.'(.*)/', $sSource, $aMatches))
		{
			$aMatches[1] = trim($aMatches[1]);
			if ($aMatches[1] == '')
			  $aMatches[1] = '1.1';
			$sParsed = self::$aDoctypes[$aMatches[1]]."\n";
		} else
		// Internal comment
		if (preg_match('/^\\'.self::TOKEN_COMMENT.'\\'.self::TOKEN_COMMENT.'/', $sSource))
			return '';
		else
		// PHP instruction
		if (preg_match('/^'.self::TOKEN_INSTRUCTION_PHP.' (.*)/', $sSource, $aMatches))
		{

			if (!$this->embedCode())
				return '';
			$bBlock = false;
			// Check for block
			if (preg_match('/^('.implode('|', self::$aPhpBlocks).')/', $aMatches[1]))
			  $this->bBlock = $bBlock = true;
			$sParsedBegin = '<?php ' . $aMatches[1] . ($bBlock ? ' { ' : ';')  . '?>'."\n";
			if ($bBlock)
			  $sParsedEnd = '<?php } ?>'."\n";
			
		} else
		// Text block
		if (preg_match('/^'.self::TOKEN_TEXT_BLOCKS.'(.+)/', $sSource, $aMatches))
		{
			$sParsed = $this->indent($this->parseTextBlock($aMatches[1], $this->getAsSource($this->iIndent)));
			$this->aChildren = array();
		} else
		// Check for PHP
		if (preg_match('/^'.self::TOKEN_PARSE_PHP.' (.*)/', $sSource, $aMatches))
			if ($this->embedCode())
				$sParsed = trim($this->indent("<?php echo {$aMatches[1]}; ?>"));
			else
				$sParsed = "\n";
		else
		{
			$aAttributes = array();
			$sAttributes = '';
			$sTag = 'div';
			$sToParse = '';
			$sContent = '';
			$sAutoVar = '';

			// Parse options
			while (preg_match('/\\'.self::TOKEN_OPTIONS_LEFT.'(.*?)\\'.self::TOKEN_OPTIONS_RIGHT.'/', $sSource, $aMatches))
			{
				$sSource = str_replace($aMatches[0], '', $sSource);
				$aOptions = explode(self::TOKEN_OPTIONS_SEPARATOR, $aMatches[1]);
				foreach ($aOptions as $sOption)
				{
					$aOption = explode(self::TOKEN_OPTION_VALUE, trim($sOption), 2);
					foreach ($aOption as $k => $o)
						$aOption[$k] = trim($o);
					$sOptionName = ltrim($aOption[0], self::TOKEN_OPTION);
					$aAttributes[$sOptionName] = trim($aOption[1], self::TOKEN_OPTION);
				}
			}

			$sFirst = '['.self::TOKEN_TAG.'|'.self::TOKEN_ID.'|'.self::TOKEN_CLASS.'|'.self::TOKEN_PARSE_PHP.']';

			if (preg_match("/^($sFirst.*?) (.*)/", $sSource, $aMatches))
			{
				$sToParse = $aMatches[1];
				$sContent = $aMatches[2];
			} 
			else if (preg_match("/^($sFirst.*)/", $sSource, $aMatches)) 
			{
				$sToParse = $aMatches[1];
			}
			else
			{
				// Check for comment
				if (!preg_match('/^\\'.self::TOKEN_COMMENT.'(.*)/', $sSource, $aMatches))
				{
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isFirst())
							$sParsed = $this->indent($sSource, 0, false) . ' '; else
						if ($this->isLast())
							$sParsed = "$sSource\n";
						else
							$sParsed = "$sSource ";
					else
						$sParsed = $this->indent($sSource);
					foreach ($this->aChildren as $oChild)
						$sParsed .= $oChild->render();
				}
				else
				{
					$aMatches[1] = trim($aMatches[1]);
					if ($aMatches[1] && !preg_match('/\[.*\]/', $aMatches[1]))
						$sParsed = $this->indent(wordwrap($aMatches[1], 60, "\n"), 1)."\n";
				}
				$bParse = false;
			}

			if ($bParse)
			{
				$bPhp = false;
				$bClosed = false;

								
				// Match tag
				if (preg_match_all('/'.self::TOKEN_TAG.'([a-zA-Z0-9:\-_]*)/', $sToParse, $aMatches))
					$this->sTag = $sTag = end($aMatches[1]); // it's stack				
				
				// Match Target
				if (preg_match('/\\'.self::TOKEN_TARGET .'.*$/', $sToParse, $aMatches))
				{
					if ($this->sTag == 'a' || $this->sTag == 'link') {
						$sUrl = str_replace(self::TOKEN_TARGET, '', $aMatches[0]);
						$phpAssigned = explode('=', $sUrl);
						if (isset($phpAssigned[1]))
							$aAttributes['href'] = $phpAssigned[1];
						else
							$aAttributes['href'] = $sUrl;
						$sToParse = str_replace($aMatches[0], '', $sToParse);
					} elseif ($this->sTag == 'script' || $this->sTag == 'img') {
	
						$sUrl = str_replace(self::TOKEN_TARGET, '', $aMatches[0]);
						$phpAssigned = explode('=', $sUrl);
						
						if (isset($phpAssigned[1])) {
							if ($this->sTag == 'script') {
								$aAttributes['src'] = '<?=$html->url("/js/'.$phpAssigned[1].'")?>';
							} elseif($this->sTag == 'img') {
								$aAttributes['src'] = '<?=$html->url("/img/'.$phpAssigned[1].'")?>';
							}							
						} else {
							$aAttributes['src'] = $sUrl;
						}
							
						if ($this->sTag == 'script') {
							$aAttributes['type']	 = 'text/javascript';
						} elseif($this->sTag == 'img') {
							if (empty($aAttributes['alt'])) $aAttributes['alt'] = '';
						}
						$sToParse = str_replace($aMatches[0], '', $sToParse);
					}
				}
	
				// Match ID
				if (preg_match_all('/'.self::TOKEN_ID.'([a-zA-Z0-9_]*)/', $sToParse, $aMatches))
					$aAttributes['id'] = '\''.end($aMatches[1]).'\''; // it's stack
					
				// Match classes
				if (preg_match_all('/\\'.self::TOKEN_CLASS.'([a-zA-Z0-9\-_]*)/', $sToParse, $aMatches)) {
					$aAttributes['class'] = '\''.implode(' ', $aMatches[1]).'\'';
				}
				
				// Check for PHP
				if (preg_match('/'.self::TOKEN_PARSE_PHP.'/', $sToParse))
				{
					if ($this->embedCode())
					{
						$sContentOld = $sContent;
						$sContent = "<?php echo $sContent; ?>";
						$bPhp = true;
					}
					else
						$sContent = '';
				}				
				
				// Match translating
				if (preg_match('/\\'.self::TOKEN_TRANSLATE.'$/', $sToParse, $aMatches))
				{
					if (!$bPhp)
						$sContent = '"'.$sContent.'"';
					else
						$sContent = $sContentOld;
					$sContent = "<?php echo {$this->sTranslate}($sContent, true); ?>";
				}
				// Match single tag
				if (preg_match('/\\'.self::TOKEN_SINGLE.'$/', $sToParse))
					$bClosed = true;
				// Match brackets
				if (preg_match('/\\'.self::TOKEN_AUTO_LEFT.'(.*?)\\'.self::TOKEN_AUTO_RIGHT.'/', $sToParse, $aMatches) && $this->embedCode())
					$sAutoVar = $aMatches[1];

				if (!empty($aAttributes) || !empty($sAutoVar)) {
					$sAttributes ='';
					foreach ($aAttributes as $attrKey => $attrVal) {
						$attrKey = str_replace("'", '', $attrKey);
						
						if (strlen($attrVal)) {
							if ($attrVal{0} == '$') {
								$attrVal = '<?php echo '.$attrVal.' ?>';
							} else {
								$attrVal = str_replace("'", '', $attrVal);
							}
						}
						
						$sAttributes.= ' '.$attrKey.'="'.$attrVal.'"';
					}
					/*
						$sAttributes = '<?php $this->writeAttributes('.$this->arrayExport($aAttributes).(!empty($sAutoVar) ? ", \$this->parseSquareBrackets($sAutoVar)" : '' ).'); ?>';
					*/
				}
				$this->bBlock = $this->oParent->bBlock;
				$iLevelM = $this->oParent->bBlock || $this->bBlock ? -1 : 0;
				// Check for closed tag
				if ($this->isClosed($sTag) || $bClosed)
					$sParsedBegin = $this->indent("<$sTag$sAttributes />", $iLevelM); else
				// Check for no indent tag
				if (in_array($sTag, self::$aNoIndentTags))
				{
					$this->bRemoveBlank = false;
					$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM, false);
					if (!empty($sContent))
						$sParsed = $this->indent($sContent);
					$sParsedEnd = $this->indent("</$sTag>\n", $iLevelM);
				} else
				// Check for block tag
				if (!$this->isInline($sTag))
				{
					$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM);
					if (!empty($sContent))
						if (strlen($sContent) > 60)
							$sParsed = $this->indent(wordwrap($sContent, 60, "\n"), 1+$iLevelM);
						else
							$sParsed = $this->indent($sContent, 1+$iLevelM);
					$sParsedEnd = $this->indent("</$sTag>", $iLevelM);
				} else
				// Check for inline tag
				if ($this->isInline($sTag))
				{
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isFirst())
							$sParsedBegin = $this->indent("<$sTag$sAttributes>", 0, false); else
						if ($this->isLast())
							$sParsedBegin = "<$sTag$sAttributes>\n";
						else
							$sParsedBegin = "<$sTag$sAttributes>";
					else
						if (!$this->canIndent())
							$sParsedBegin = "\n" . $this->indent("<$sTag$sAttributes>", $iLevelM, false);
						else
							$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM, false);
					$sParsed = $sContent;
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isLast())
							$sParsedEnd = "</$sTag>\n";
						else
							$sParsedEnd = "</$sTag> ";
					else
						$sParsedEnd = "</$sTag>\n";
				}
			}
		}
		// Children appending
		foreach ($this->aChildren as $oChild)
			$sParsed .= $oChild->fetch();
		// Check for IE comment
		if (preg_match('/^\\'.self::TOKEN_COMMENT.'\[(.*?)\](.*)/', $sSource, $aMatches))
		{
			$aMatches[2] = trim($aMatches[2]);
			if (count($this->aChildren) == 0)
			{
				$sParsedBegin = $this->indent("<!--[{$aMatches[1]}]> $sParsedBegin", 0, false);
				$sParsed = $aMatches[2];
				$sParsedEnd = "$sParsedEnd <![endif]-->\n";
			}
			else
			{
				$sParsed = $sParsedBegin.$sParsed.$sParsedEnd;
				$sParsedBegin = $this->indent("<!--[{$aMatches[1]}]>");
				$sParsedEnd = $this->indent("<![endif]-->");
			}
		} else
		// Check for comment
		if (preg_match('/^\\'.self::TOKEN_COMMENT.'(.*)/', $sSource, $aMatches))
		{
			$aMatches[1] = trim($aMatches[1]);
			if (count($this->aChildren) == 0)
			{
				$sParsedBegin = $this->indent("<!-- $sParsedBegin", 0, false);
				$sParsed = $aMatches[1];
				$sParsedEnd = "$sParsedEnd -->\n";
			}
			else
			{
				$sParsed = $sParsedBegin.$sParsed.$sParsedEnd;
				$sParsedBegin = $this->indent("<!--");
				$sParsedEnd = $this->indent("-->");
			}
		}
		if ($this->isDebug() && (count($this->aChildren) > 0))
			$sParsedEnd = "{$this->aDebug['line']}:\t$sParsedEnd";
		$sCompiled = $sRealBegin.$sParsedBegin.$sParsed.$sParsedEnd.$sRealEnd;
		if ($this->isDebug())
			$sCompiled = "{$this->aDebug['line']}:\t$sCompiled";
		return $sCompiled;
	}

	/**
	 * Indent line
	 *
	 * @param string Line
	 * @param integer Additional indention level
	 * @param boolean Add new line
	 * @param boolean Count level from parent
	 * @return string
	 */
	protected function indent($sLine, $iAdd = 0, $bNew = true, $bCount = true)
	{
		if (!is_null($this->oParent) && $bCount)
			if (!$this->canIndent())
				if ($sLine{0} == '<')
					return $sLine;
				else
					return "$sLine\n";
		$aLine = explode("\n", $sLine);
		$sIndented = '';
		$iLevel = ($bCount ? $this->iIndent : 0) + $iAdd;
		foreach ($aLine as $sLine)
			$sIndented .= str_repeat('  ', $iLevel >= 0 ? $iLevel : 0).($bNew ? "$sLine\n" : $sLine);
		return $sIndented;
	}

	/**
	 * Is first child of parent
	 *
	 * @return boolean
	 */
	protected function isFirst()
	{
		if (!$this->oParent instanceof HamlParser)
			return false;
		foreach ($this->oParent->aChildren as $key => $value)
			if ($value === $this)
				return $key == 0;
	}

	/**
	 * Is last child of parent
	 *
	 * @return boolean
	 */
	protected function isLast()
	{
		if (!$this->oParent instanceof HamlParser)
			return false;
		$count = count($this->oParent->aChildren);
		foreach ($this->oParent->aChildren as $key => $value)
			if ($value === $this)
				return $key == $count - 1;
	}

	/**
	 * Can indent (check for parent is NoIndentTag)
	 *
	 * @return boolean
	 */
	public function canIndent()
	{
		if (in_array($this->sTag, self::$aNoIndentTags))
			return false;
		else
			if ($this->oParent instanceof HamlParser)
				return $this->oParent->canIndent();
			else
				return true;
	}

	/**
	 * Indent Haml source
	 *
	 * @param string Source
	 * @param integer Level
	 * @return string
	 */
	protected function sourceIndent($sSource, $iLevel)
	{
		$aSource = explode(self::TOKEN_LINE, $sSource);
		foreach ($aSource as $sKey => $sValue)
			$aSource[$sKey] = str_repeat(self::TOKEN_INDENT, $iLevel * self::INDENT) . $sValue;
		$sSource = implode(self::TOKEN_LINE, $aSource);
		return $sSource;
	}

	/**
	 * Count level of line
	 *
	 * @param string Line
	 * @return integer
	 */
	protected function countLevel($sLine)
	{
		return (strlen($sLine) - strlen(trim($sLine, self::TOKEN_INDENT))) / self::INDENT;
	}

	/**
	 * Check for inline tag
	 *
	 * @param string Tag
	 * @return boolean
	 */
	protected function isInline($sTag)
	{
		return (empty($this->aChildren) && in_array($sTag, self::$aInlineTags)) || empty($this->aChildren);
	}

	/**
	 * Check for closed tag
	 *
	 * @param string Tag
	 * @return boolean
	 */
	protected function isClosed($sTag)
	{
		return in_array($sTag, self::$aClosedTags);
	}

	/**
	 * End of line character
	 */
	const TOKEN_LINE = "\n";

	/**
	 * Indention token
	 */
	const TOKEN_INDENT = "\t";

	/**
	 * Create tag (%strong, %div)
	 */
	const TOKEN_TAG = '%';

	/**
	 * Set element ID (#foo, %strong#bar)
	 */
	const TOKEN_ID = '#';

	/**
	 * Set element class (.foo, %strong.lorem.ipsum)
	 */
	const TOKEN_CLASS = '.';

	/**
	 * Start the options (attributes) list
	 */
	const TOKEN_OPTIONS_LEFT = '{';

	/**
	 * End the options list
	 */
	const TOKEN_OPTIONS_RIGHT = '}';

	/**
	 * Options separator
	 */
	const TOKEN_OPTIONS_SEPARATOR = ',';

	/**
	 * Start option name
	 */
	const TOKEN_OPTION = ':';

	/**
	 * Start option value
	 */
	const TOKEN_OPTION_VALUE = '=>';

	/**
	 * Begin PHP instruction (without displaying)
	 */
	const TOKEN_INSTRUCTION_PHP = '-';

	/**
	 * Parse PHP (and display)
	 */
	const TOKEN_PARSE_PHP = '=';

	/**
	 * Set DOCTYPE or XML header (!!! 1.1, !!!, !!! XML)
	 */
	const TOKEN_DOCTYPE = '!!!';

	/**
	 * Include file (!! tpl2)
	 */
	const TOKEN_INCLUDE = '!!';

	/**
	 * Comment code (block and inline)
	 */
	const TOKEN_COMMENT = '/';
	
	/**
	 * Comment code (block and inline)
	 */
	const TOKEN_TARGET = '@';	

	/**
	 * Translate content (%strong$ Translate)
	 */
	const TOKEN_TRANSLATE = '$';

	/**
	 * Mark level (%strong?3, !! foo?3)
	 */
	const TOKEN_LEVEL = '?';

	/**
	 * Create single, closed tag (%meta{ :foo => 'bar'}/)
	 */
	const TOKEN_SINGLE = '/';

	/**
	 * Break line
	 */
	const TOKEN_BREAK = '|';

	/**
	 * Begin automatic id and classes naming (%tr[$model])
	 */
	const TOKEN_AUTO_LEFT = '[';

	/**
	 * End automatic id and classes naming
	 */
	const TOKEN_AUTO_RIGHT = ']';

	/**
	 * Insert text block (:textile)
	 */
	const TOKEN_TEXT_BLOCKS = ':';

	/**
	 * Number of TOKEN_INDENT to indent
	 */
	const INDENT = 1;

	/**
	 * Doctype definitions
	 *
	 * @var string
	 */
	protected static $aDoctypes = array
	(
		'1.1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
		'Strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'Transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'Frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'XML' => "<?php echo '<?xml version=\"1.0\" encoding=\"utf-8\"?>'; ?>\n"
	);

	/**
	 * List of inline tags
	 *
	 * @var array
	 */
	public static $aInlineTags = array
	(
		'a', 'strong', 'b', 'em', 'i', 'h1', 'h2', 'h3', 'h4',
		'h5', 'h6', 'span', 'title', 'li', 'dt', 'dd', 'code',
		'cite', 'td', 'th', 'abbr', 'acronym', 'legend', 'label'
	);

	/**
	 * List of closed tags (like br, link...)
	 *
	 * @var array
	 */
	public static $aClosedTags = array('br', 'hr', 'link', 'meta', 'img', 'input');

	/**
	 * List of tags which can't be indented
	 *
	 * @var array
	 */
	public static $aNoIndentTags = array('pre', 'textarea');

	/**
	 * List of PHP blocks
	 *
	 * @var array
	 *
	 */
	protected static $aPhpBlocks = array('if', 'else', 'elseif', 'while', 'switch', 'for', 'do');

	// Template engine

	/**
	 * Template variables
	 *
	 * @var array
	 */
	protected static $aVariables = array();

	/**
	 * Assign variable
	 *
	 * <code>
	 * // ...
	 * $parser->assign('foo', 'bar');
	 * $lorem = 'ipsum';
	 * $parser->assign('example', $lorem);
	 * </code>
	 *
	 * @param string Name
	 * @param mixed Value
	 * @return object
	 */
	public function assign($sName, $sValue)
	{
		self::$aVariables[$sName] = $sValue;
		return $this;
	}

	public function assign_by_ref($sName, $sValue)
	{
		self::$aVariables[$sName] = &$sValue;
		return $this;
	}
	
	/**
	 * Assign associative array of variables
	 *
	 * <code>
	 * // ...
	 * $parser->append(array('foo' => 'bar', 'lorem' => 'ipsum');
	 * $data = array
	 * (
	 *   'x' => 10,
	 *   'y' => 5
	 * );
	 * $parser->append($data);
	 * </code>
	 *
	 * @param array Data
	 * @return object
	 */
	public function append($aData)
	{
		self::$aVariables = array_merge(self::$aVariables, $aData);
		return $this;
	}

	/**
	 * Removes variables
	 *
	 * @return object
	 */
	public function clearVariables()
	{
		self::$aVariables = array();
		return $this;
	}

	/**
	 * Remove all compiled templates (*.hphp files)
	 *
	 * @return object
	 */
	public function clearCompiled()
	{
		$oDirs = new DirectoryIterator($this->sTmp);
		foreach ($oDirs as $oDir)
			if (!$oDir->isDot())
				if (preg_match('/\.hphp/', $oDir->getPathname()))
				unlink($oDir->getPathname());
				return $this;
	}

	/**
	 * Return compiled template
	 *
	 * <code>
	 * // ...
	 * echo $parser->setSource('%strong Foo')->fetch(); // <strong>Foo</strong>
	 * $parser->setSource('%strong Bar')->display(); // <strong>Bar</strong>
	 * echo $parser->setSource('%em Linux'); // <strong>Linux</strong>
	 *
	 * echo $parser->fetch('bar.haml'); // Compile and display bar.haml
	 * </code>
	 *
	 * @param string Filename
	 * @return string
	 */
	public function fetch($sFilename = false)
	{
		if ($sFilename)
			$this->setFile($sFilename);
		return $this->render();
	}

	/**
	 * Display template
	 *
	 * @see HamlParser::fetch()
	 * @param string Filename
	 */
	public function display($sFilename = false)
	{
		echo $this->fetch($sFilename);
	}

	/**
	 * List of registered filters
	 *
	 * @var array
	 */
	protected $aFilters = array();

	/**
	 * Register output filter.
	 *
	 * Filters are next usefull stuff. For example if
	 * you want remove <em>all</em> whitespaces (blah) use this
	 * <code>
	 * // ...
	 * function fcw($data)
	 * {
	 *   return preg_replace('|\s*|', '', $data);
	 * }
	 * $parser->registerFilter('fcw');
	 * echo $parser->fetch('foo.haml');
	 * </code>
	 *
	 * @param callable Filter
	 * @param string Name
	 * @return object
	 */
	public function registerFilter($mCallable, $sName = false)
	{
		if (!$sName)
			$sName = serialize($mCallable);
		$this->aFilters[$sName] = $mCallable;
		return $this;
	}

	/**
	 * Unregister output filter
	 *
	 * @param string Name
	 * @return object
	 */
	public function unregisterFilter($sName)
	{
		unset($this->aFilters[$sName]);
		return $this;
	}

	/**
	 * Return array of template variables
	 *
	 * @return array
	 */
	public function getVariables()
	{
		return self::$aVariables;
	}

	/**
	 * Parse variable in square brackets
	 *
	 * @param mixed Variable
	 * @return array Attributes
	 */
	protected function parseSquareBrackets($mVariable)
	{
		$sType = gettype($mVariable);
		$aAttr = array();
		$sId = '';
		if ($sType == 'object')
		{
			static $__objectNamesCache;
			if (!is_array($__objectNamesCache))
				$__objectNamesCache = array();
			$sClass = get_class($mVariable);
			if (!array_key_existS($sClass, $__objectNamesCache))
				$__objectNamesCache[$sClass] = $sType = trim(preg_replace('/([A-Z][a-z]*)/', '$1_', $sClass), '_');
			else
				$sType = $__objectNamesCache[$sClass];
			if (method_exists($mVariable, 'getID'))
				$sId = $mVariable->getID(); else
			if (!empty($mVariable->ID))
				$sId = $mVariable->ID;
		}
		if ($sId == '')
			$sId = substr(md5(uniqid(serialize($mVariable).rand(), true)), 0, 8);
		$aAttr['class'] = strtolower($sType);
		$aAttr['id'] = "{$aAttr['class']}_$sId";
		return $aAttr;
	}

	/**
	 * Write attributes
	 */
	protected function writeAttributes()
	{
		$aAttr = array();
		foreach (func_get_args() as $aArray)
			$aAttr = array_merge($aAttr, $aArray);
		ksort($aAttr);
		foreach ($aAttr as $sName => $sValue)
			if ($sValue)
				echo " $sName=\"".htmlentities($sValue).'"';
	}

	/**
	 * Export array
	 *
	 * @return string
	 */
	protected function arrayExport()
	{
		$sArray = 'array (';
		$aArray = $aNArray = array();
		foreach (func_get_args() as $aArg)
			$aArray = array_merge($aArray, $aArg);
		foreach ($aArray as $sKey => $sValue)
		{
			if (!preg_match('/[\'$"()]/', $sValue))
				$sValue = "'$sValue'";
			$aNArray[] = "'$sKey' => $sValue";
		}
		$sArray .= implode(', ', $aNArray).')';
		return $sArray;
	}
}

if (!function_exists('fake_translate'))
{
	/**
	 * Fake translation function used
	 * as default translation function
	 * in HamlParser
	 *
	 * @param string
	 * @return string
	 */
	function fake_translate($s)
	{
		return __($s, true);
	}
}

/**
 * This is the simpliest way to use Haml
 * templates. Global variables are
 * automatically assigned to template.
 *
 * <code>
 * $x = 10;
 * $y = 5;
 * display_haml('my.haml'); // Simple??
 * </code>
 *
 * @param string Haml parser filename
 * @param array Associative array of additional variables
 * @param string Temporary directory (default is directory of Haml templates)
 * @param boolean Register get, post, session, server and cookie variables
 */
function display_haml($sFilename, $aVariables = array(), $sTmp = true, $bGPSSC = false)
{
	global $__oHaml;
	$sPath = realpath($sFilename);
	if (!is_object($__oHaml))
		$__oHaml = new HamlParser(dirname($sPath), $sTmp);
	$__oHaml->append($GLOBALS);
	if ($bGPSSC)
	{
		$__oHaml->append($_GET);
		$__oHaml->append($_POST);
		$__oHaml->append($_SESSION);
		$__oHaml->append($_SERVER);
		$__oHaml->append($_COOKIE);
	}
	$__oHaml->append($aVariables);
	$__oHaml->display($sFilename);
}

?>