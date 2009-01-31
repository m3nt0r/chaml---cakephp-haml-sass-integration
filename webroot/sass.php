<?php
if (!defined('CAKE_CORE_INCLUDE_PATH')) {
	header('HTTP/1.1 404 Not Found');
	exit('File Not Found');
}

/**
 * SASS CSS FILTER
 * 
 * create corresponding .sass file in webroot/css/
 * done.
 */
header("Content-Type: text/css");

if (preg_match('|\.\.|', $url) || !preg_match('|^ccss/(.+)$|i', $url, $regs)) {
	die('Wrong file name.');
}

$filepath = CSS . $regs[1];
$sassFile = r('.css', '.sass', $filepath);

if (!file_exists($sassFile)) die('/*SASS file not found.*/');

// RENDER AND CACHE
App::import('Vendor', 'SassParser', array('file'=>'phphaml'.DS.'sass'.DS.'SassParser.class.php'));
$renderer = SassRenderer::COMPACT;
$parser = new SassParser(CSS, TMP.'sass', $renderer);

// OUTPUT
echo "/* SASS - ".$parser->getRenderer()." */\n";
echo $parser->fetch($sassFile, $renderer);
?>