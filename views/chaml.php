<?php
/*
 * CHAML - HAML for CakePHP
 *
 * -- we are at experimental stage --
 *
 * @version 0.1
 * @author Kjell Bublitz <m3nt0r.de@gmail.com>
 * @link http://github.com/m3nt0r/chaml---cakephp-haml-sass-integration
 * @link http://cakealot.com Authors Weblog
 * @copyright 2008-2009 (c) Kjell Bublitz
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package cake-bits
 * @subpackage datasource
 * 
 * Based on the work by Adeel Khan (chess64) - cheers!
 */
App::import('Vendor', 'HamlParser', array('file'=>'haml'.DS.'HamlParser.class.php'));
App::import('Vendor', 'Markdown', array('file'=>'markdown'.DS.'markdown.php'));

// Default Config
if (is_null(Configure::read('HAML.debug'))) Configure::write('HAML.debug', false);
if (is_null(Configure::read('HAML.contentIndent'))) Configure::write('HAML.contentIndent', 4);
if (is_null(Configure::read('HAML.compressHTML'))) Configure::write('HAML.compressHTML', false);
if (is_null(Configure::read('HAML.showCacheTime'))) Configure::write('HAML.showCacheTime', false);
if (is_null(Configure::read('HAML.noCache'))) Configure::write('HAML.noCache', false);

/**
 * CakePHP HAML Parser Extension
 * 
 * Some overwrites and extensions to the original parser.
 * However.. the original parser is hacked too.
 */
class CakeHamlParser extends HamlParser 
{ 
	public $indentBy = 4;
	public $sTranslate = '__';
	const TOKEN_INDENT = "  ";
	
	/**
	* Setup Haml and support configure
	*
	* @param unknown_type $oParent
	* @param unknown_type $aDebug
	* @param unknown_type $bInside
	*/
	function __construct($view, $oParent = null, $aDebug = null, $bInside = false) 
	{
		$this->cakeView = $view;
		
		$config = Configure::read('HAML');
		
		$this->isDebug($config['debug']);
		$this->indentBy = $config['contentIndent'];
		$this->showCacheTime = $config['showCacheTime'];
		$this->compress = $config['compressHTML'];
		$this->noCache = $config['noCache'];
				
		parent::__construct(VIEWS, TMP . 'haml', $oParent, $aDebug, $bInside);
	}

	/**
	 * Tell HAML what a element is
	 */
	public function element($tpl, $params = array(), $helpers = false) {
		if (isset($this->cakeView)) {
			return $this->cakeView->element($tpl, $params, $helpers);
		}
	}

	/**
	* Indent source by X .. works on any code.
	*
	* @param unknown_type $sSource
	* @param unknown_type $iLevel
	* @return unknown
	*/
	public function indentContent($sSource, $iLevel) {
		$aSource = explode(self::TOKEN_LINE, $sSource);
		foreach ($aSource as $sKey => $sValue)
			$aSource[$sKey] = str_repeat(self::TOKEN_INDENT, $iLevel * self::INDENT) . $sValue;
		$sSource = implode(self::TOKEN_LINE, $aSource);
		return $sSource;
	}
}

/**
 * The Actual HAML View
 * 
 */
class ChamlView extends View {

	var $ext = '.haml';
	
	function __construct(&$controller) {
		parent::__construct($controller);
		
		$this->ext = '.haml';
		
		$this->Haml = new CakeHamlParser($this);
		foreach ($this as $prop => $value) {
			$this->Haml->{$prop} = $value;
		}
		
		$this->Haml->assign_by_ref('__haml', $this->Haml);
	}
	
	/**
	 * Extend Layout Render for Nice Indention
	 *
	 * @param string $content_for_layout
	 * @return string 
	 */
	function renderLayout($content_for_layout) {
		if ($content_for_layout) {
			$whitespace = CakeHamlParser::INDENT * ($this->Haml->indentBy-1);
			$content_for_layout = $this->Haml->indentContent($content_for_layout, $this->Haml->indentBy);
			$content_for_layout = "\n".$content_for_layout."\n";
			$content_for_layout.= str_repeat(" ", $whitespace);
		}
		return parent::renderLayout($content_for_layout);
	}
	
	/**
	 * Overwrite Render to use HAML
	 *
	 * @param unknown_type $action
	 * @param unknown_type $layout
	 * @param unknown_type $file
	 * @return unknown
	 */
	function render($action = null, $layout = null, $file = null) {

		if (isset($this->hasRendered) && $this->hasRendered) {
			return true;
		} else {
			$this->hasRendered = false;
		}

		if (!$action) {
			$action = $this->action;
		}

		if ($layout) {
			$this->layout = $layout;
		}

		if ($file) {
			$viewFileName = $file;
			$this->_missingView($viewFileName, $action);
		} else {
			$viewFileName = $this->_getViewFileName($action);
		}

		if ($viewFileName && !$this->hasRendered) {
			$content_for_layout = $this->_render($viewFileName, $this->viewVars);
			
			if ($content_for_layout !== false) 
			{
				unset($this->Haml);
				$this->Haml = new CakeHamlParser($this);
				foreach ($this as $prop => $value) {
					$this->Haml->{$prop} = $value;
				}				
					
				$this->Haml->assign_by_ref('__haml', $this->Haml);
				if ($this->layout && $this->autoLayout) {
					$layout = $this->renderLayout($content_for_layout);
					if (isset($this->loaded['cache']) && (($this->cacheAction != false)) && (defined('CACHE_CHECK') && CACHE_CHECK === true)) {
						$replace = array('<cake:nocache>', '</cake:nocache>');
						$layout = str_replace($replace, '', $layout);
					}
				}
				print $layout;
				$this->hasRendered = true;
			} 
			else 
			{
				$layout = $this->_render($viewFileName, $this->viewVars);
				trigger_error(sprintf(__("Error in view %s, got: <blockquote>%s</blockquote>", true), $viewFileName, $layout), E_USER_ERROR);
			}
			//return true;
		}
	}
	
	/**
	 * Helpers and stuff
	 * 
	 * @param unknown_type $___viewFn
	 * @param unknown_type $___data_for_view
	 * @param unknown_type $___play_safe
	 * @param unknown_type $loadHelpers
	 * @return unknown
	 */
	function _render($___viewFn, $___data_for_view, $___play_safe = true, $loadHelpers = true) {

		if ($this->helpers != false && $loadHelpers === true) {
			$loadedHelpers =  array();
			$loadedHelpers = $this->_loadHelpers($loadedHelpers, $this->helpers);

			foreach (array_keys($loadedHelpers) as $helper) {
				$replace = strtolower(substr($helper, 0, 1));
				$camelBackedHelper = preg_replace('/\\w/', $replace, $helper, 1);
				${$camelBackedHelper} =& $loadedHelpers[$helper];
				if (isset(${$camelBackedHelper}->helpers) && is_array(${$camelBackedHelper}->helpers)) {
					foreach (${$camelBackedHelper}->helpers as $subHelper) {
						${$camelBackedHelper}->{$subHelper} =& $loadedHelpers[$subHelper];
					}
				}
				$this->loaded[$camelBackedHelper] = (${$camelBackedHelper});
				$this->Haml->assign_by_ref($camelBackedHelper, ${$camelBackedHelper});
			}
		}

		foreach ($___data_for_view as $data => $value) {
			if (!is_object($data)) {
				$this->Haml->assign($data, $value);
			}
		}
		$this->Haml->assign_by_ref('view', $this);
		return $this->Haml->fetch($___viewFn);
	}
}
?>