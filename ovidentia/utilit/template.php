<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 * @package Core
 */






function &bab_TemplateCache_getStore()
{
	static $cacheStore = null;
	if ($cacheStore === null) {
		$cacheStore = array();
	}
	return $cacheStore;
}




/*
 * Class used in section babMeta in template config.html
 */
class bab_configTemplate_sectionBabmeta {

	/*
	 * Text used as a value to the html meta tag : <meta http-equiv="Content-type" content="{ sContent }" />
	 * @var string
	 */
	public $sContent;

	public function __construct() {
		$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
	}
}



/**
 * White-list of global variables accessible by a template.
 *
 * @param string $var	The name of the global variable.
 * @return string
 */
function getGlobalVariable($var)
{


	switch($var)
	{
		case 'babCss': return bab_printTemplate(new stdClass, "config.html", "babCss");
		case 'babMeta': $object = new bab_configTemplate_sectionBabmeta(); return bab_printTemplate($object, "config.html", "babMeta");
		case 'babsectionpuce': return bab_printTemplate(new stdClass, "config.html", "babSectionPuce");
		case 'babsectionbullet': return bab_printTemplate(new stdClass, "config.html", "babSectionBullet");
		case 'babIE': return (( mb_strtolower(bab_browserAgent()) == "msie") && (bab_browserOS() == "windows")) ? 1 : 0;
		case 'babCssPath': return bab_toHtml(bab_getCssUrl());
		case 'babScriptPath': return bab_toHtml($GLOBALS['babScriptPath']);
		case 'babOvidentiaJs': return $GLOBALS['babOvidentiaJs'];
		case 'babOvmlPath': return bab_toHtml($GLOBALS['babOvmlPath']);
		case 'babSkinPath': return bab_toHtml($GLOBALS['babSkinPath']); // skin in core
		case 'selectedSkinPath': return bab_skin::getUserSkin()->getThemePath(); // path to the selected skin folder
		case 'babLanguage': return bab_toHtml($GLOBALS['babLanguage']);
		case 'babStyle': return bab_toHtml($GLOBALS['babStyle']);
		case 'babSkin': return bab_toHtml($GLOBALS['babSkin']);
		case 'babSiteName': return bab_toHtml($GLOBALS['babSiteName']);
		case 'BAB_SESS_USERID': return bab_toHtml($GLOBALS['BAB_SESS_USERID']);
		case 'BAB_SESS_NICKNAME': return bab_toHtml($GLOBALS['BAB_SESS_NICKNAME']);
		case 'BAB_SESS_USER': return bab_toHtml($GLOBALS['BAB_SESS_USER']);
		case 'BAB_SESS_FIRSTNAME': return bab_toHtml($GLOBALS['BAB_SESS_FIRSTNAME']);
		case 'BAB_SESS_LASTNAME': return bab_toHtml($GLOBALS['BAB_SESS_LASTNAME']);
		case 'BAB_SESS_EMAIL': return bab_toHtml($GLOBALS['BAB_SESS_EMAIL']);
		case 'babPhpSelf': return bab_toHtml($GLOBALS['babPhpSelf']);
		case 'babUrl': return bab_toHtml($GLOBALS['babUrl']);
		case 'staticUrl': return bab_toHtml(bab_getStaticUrl());
		case 'babInstallPath': return bab_toHtml($GLOBALS['babInstallPath']);
		case 'babUrlScript': return bab_toHtml($GLOBALS['babUrlScript']);
		case 'babAddonUrl': return bab_toHtml($GLOBALS['babAddonUrl']);
		case 'babAddonTarget': return bab_toHtml($GLOBALS['babAddonTarget']);
		case 'babAddonHtmlPath': return bab_toHtml($GLOBALS['babAddonHtmlPath']);
		case 'babSlogan': return bab_toHtml($GLOBALS['babSlogan']);
		case 'babAdminEmail': return bab_toHtml($GLOBALS['babAdminEmail']);
		case 'babMaxFileSize' : return bab_toHtml($GLOBALS['babMaxFileSize']);
		case 'babAddonFolder' : return bab_toHtml($GLOBALS['babAddonFolder']);
		case 'tg': return bab_toHtml(bab_rp('tg'));
		case 'idx': if(is_array(bab_rp('idx'))){return '';} return bab_toHtml((string) bab_rp('idx'));
		case bab_CsrfProtect::FIELDNAME: return bab_toHtml(bab_getInstance('bab_CsrfProtect')->getToken());
	}
	return NULL;
}

/**
 * This class is used to cache the parsed templates in memory.
 */
class bab_TemplateCache
{
	/**
	 * Tries to retrieve a parsed template from the cache. Returns null if the specified
	 * template is not in the cache.
	 *
	 * @param string	$filename	The path of the template file.
	 * @param string	$section	The name of the section in the template file.
	 * @return string|null			The cached parsed template or null.
	 * @access public
	 * @static
	 */
	public static function get($filename, $section)
	{
		$cache =& bab_TemplateCache_getStore();
		if (isset($cache[$filename . '/' . $section])) {
			return $cache[$filename . '/' . $section];
		}
		return null;
	}

	/**
	 * Stores a parsed template in the cache.
	 *
	 * @param string	$filename			The path of the template file.
	 * @param string	$section			The name of the section in the template file.
	 * @param string	$parsedTemplate		The parsed template to cache.
	 * @access public
	 * @static
	 */
	public static function set($filename, $section, $parsedTemplate)
	{
		$cache =& bab_TemplateCache_getStore();
		$cache[$filename . '/' . $section] = $parsedTemplate;
	}
}



function bab_phpHighlightSyntax($templateString, $highlightLineNumber = -1)
{
	$lines = preg_split('/\n|\r\n|\r/', $templateString);

	$highlightedTemplateString = '<ol style="font-weight: normal">';
	$lineNumber = 1;
	foreach ($lines as $line) {
		$line = highlight_string($line, true);
		if ($lineNumber == $highlightLineNumber) {
			$highlightedTemplateString .= '<li style="font-weight: bold; padding: 2px; border-bottom: 1px solid white; background-color: #FFBBBB">' . $line . '</li>';
		} else {
			$highlightedTemplateString .= '<li style="padding: 2px; border-bottom: 1px solid white; background-color: #F7F7F7">' . $line . '</li>';
		}
		$lineNumber++;
	}
	$highlightedTemplateString .= '</ol>';

	return $highlightedTemplateString;
}

function bab_templateHighlightSyntax($templateString, $highlightLineNumber = -1)
{
	$lines = preg_split('/\n|\r\n|\r/', $templateString);

	$highlightedTemplateString = '<ol style="font-weight: normal">';
	$lineNumber = 1;
	foreach ($lines as $line) {
		$line = preg_replace('/(&lt;!--#(?:if|else|endif|in|endin)\s+(?:(?:[A-Za-z0-9_\[\]]+)\s+)?--&gt;)/', '<span style="color: #4466DD; font-weight: bold">$1</span>', bab_toHtml($line));
		if ($lineNumber == $highlightLineNumber) {
			$highlightedTemplateString .= '<li style="font-weight: bold; padding: 2px; border-bottom: 1px solid white; background-color: #FFBBBB">' . $line . '</li>';
		} else {
			$highlightedTemplateString .= '<li style="padding: 2px; border-bottom: 1px solid white; background-color: #F7F7F7">' . $line . '</li>';
		}
		$lineNumber++;
	}
	$highlightedTemplateString .= '</ol>';

	return $highlightedTemplateString;
}




/**
 * The bab_Template class implements the template engine of Ovidentia. It compiles the templates to
 * php and caches them in memory.
 * It can be used statically by calling bab_Template::printTemplate(), or it can be used
 * as the base class of a template and then be called on objects of this class, eg. $tpl->process();
 */
class bab_Template
{
	private $_templateString;
	private $_parsedTemplate;
	private $_errors;


	/**
	 * Extracts a template section from the specified template file.
	 *
	 * @param string	$pathname	The pathname to the template file.
	 * @param string	$section	The section name in the template file.
	 * @return string				The content of the section or false if not found.
	 */
	private function _loadSection($pathname, $section)
	{
		$templateFile = @fopen($pathname, 'r');
		if ($templateFile === false) {
			return false;
		}
		$quotedSection = preg_quote($section, '/');
		$sectionStart = '/<!--#begin\s+' . $quotedSection . '\s+-->/';
		$sectionEnd = '/<!--#end\s+' . $quotedSection . '\s+-->/';

		for ($sectionStartFound = false; !feof($templateFile); ) {
			$line = fgets($templateFile, 8192);
			if (preg_match($sectionStart, $line)) {
				$sectionStartFound = true;
				break;
			}
		}
		if (!$sectionStartFound) {
			return false;
		}
		$sectionContent = '';
		for ($sectionEndFound = false; !feof($templateFile); ) {
			$line = fgets($templateFile, 8192);
			if (preg_match($sectionEnd, $line)) {
				$sectionEndFound = true;
				break;
			}
			$sectionContent .= $line;
		}
		if (!$sectionEndFound) {
			return false;
		}
		fclose($templateFile);
		return $sectionContent;
	}


	/**
	 * Returns the (unparsed) content of a template file or template section.
	 *
	 * @param	string	$pathname	The pathname to the template file.
	 * @param	string	$section	The section name in the template file.
	 * 								If empty, the whole template file is loaded.
	 * @return	string				The template content or false if not found.
	 */
	public function _loadTemplate($pathname, $section)
	{
		if (!empty($section)) {
			return $this->_loadSection($pathname, $section);
		}

		if (!file_exists($pathname)) {
		    return false;
		}

		return file_get_contents($pathname);
	}


	/**
	 * Format a template so that it can be displayed for debugging purpose.
	 *
	 * Returns a string containing the formatted template.
	 *
	 * @param	string	$templateString			The Ovidentia template.
	 * @param	int		$highlightLineNumber	The line number to highlight or -1 if no line to hightlight.
	 * @return	string							The formatted template.
	 */
	public static function highlightSyntax($templateString, $highlightLineNumbers = array())
	{
		$lines = preg_split('/\n|\r\n|\r/', $templateString);

		$highlightedTemplateString = '<ol style="font-weight: normal">';
		$lineNumber = 1;
		foreach ($lines as $line) {
			$line = preg_replace('/(&lt;!--#(?:if|else|endif|in|endin)\s+(?:(?:[A-Za-z0-9_\[\]]+)\s+)?--&gt;)/', '<span style="color: #4466DD; font-weight: bold">$1</span>', bab_toHtml($line));
			if (isset($highlightLineNumbers[$lineNumber])) {
				$highlightedTemplateString .= '<li style="font-weight: bold; padding: 2px; border-bottom: 1px solid white; background-color: #FFBBBB">' . $line . '</li>';
			} else {
				$highlightedTemplateString .= '<li style="padding: 2px; border-bottom: 1px solid white; background-color: #F7F7F7">' . $line . '</li>';
			}
			$lineNumber++;
		}
		$highlightedTemplateString .= '</ol>';

		return $highlightedTemplateString;
	}


	/**
	 * Removes all errors associated to the template $templateObject
	 *
	 * @param	object	$templateObject		The template object.
	 */
	private static function resetErrors(&$templateObject)
	{
		$templateObject->_errors = array();
	}


	/**
	 * Adds an error to the template $templateObject
	 *
	 * @param	object	$templateObject		The template object.
	 * @param	string	$errorMessage		The message associated to the error
	 * @param	int		$lineNumber			The line number where the error occured in the template
	 * 										or -1 for no specific line number
	 */
	private static function addError(&$templateObject, $errorMessage, $lineNumber = -1)
	{
		if (!isset($templateObject->_errors)) {
			$templateObject->_errors = array();
		}
		$error = array('message' => $errorMessage, 'line' => $lineNumber);
		$templateObject->_errors[] = $error;
	}


	/**
	 * Returns a string containing the processed template.
	 *
	 * @param	object	$template	The template object.
	 * @param	string	$filename	The path to the template file.
	 * @param	string	$section	The optional section name in the template file.
	 * 								If not specified or empty, the whole template file is
	 * 								processed.
	 * @return	string				The processed template or null.
	 */
	public function printTemplate(&$template = null, $filename = '', $section = '')
	{
		bab_Template::resetErrors($template);
		$this->_parsedTemplate = bab_TemplateCache::get($filename, $section);
		if ($this->_parsedTemplate === null) {
			$this->_templateString = bab_Template::_loadTemplate($filename, $section);
			if ($this->_templateString === false) {
				return null;
			}
			$this->_parsedTemplate = bab_Template::_parseTemplate($this->_templateString, '$template');
			bab_TemplateCache::set($filename, $section, $this->_parsedTemplate);
		}

		ob_start();
		if (eval('?>' . $this->_parsedTemplate) === false) {
			$errorMessage = ob_get_contents();
			$matches = null;
			$lineNumber = preg_match('/line ([0-9]+)$/', strip_tags($errorMessage), $matches) ? $matches[1] : -1;
			bab_Template::addError($template, $errorMessage, $lineNumber);
			$processedTemplate = '';
		} else {
			$processedTemplate = ob_get_contents();
		}
		ob_end_clean();

		if (isset($template->_errors) && is_array($template->_errors) && count($template->_errors) > 0) {
			$errors = array();
			foreach ($template->_errors as $error) {
				bab_debug('Line ' . $error['line'] . ': ' . $error['message']);
				if (isset($errors[$error['line']])) {
					$errors[$error['line']] .= '<br />' . $error['message'];
				} else {
					$errors[$error['line']] = $error['message'];
				}
			}
			if (!isset($this->_templateString)) {
				$this->_templateString = bab_Template::_loadTemplate($filename, $section);
			}
			bab_debug('Template filename (' . $filename . ') section (' . $section . '):<br \>' . bab_Template::highlightSyntax($this->_templateString, $errors));
			bab_debug('Parsed template :<br \>' . bab_Template::highlightSyntax($this->_parsedTemplate, $errors));
		}
		return $processedTemplate;
	}


	/**
	 * Returns a string containing the processed template.
	 *
	 * @param	string	$filename	The path to the template file.
	 * @param	string	$section	The optional section name in the template file.
	 * 								If not specified or empty, the whole template file is
	 * 								processed.
	 * @return	string			The processed template or null.
	 */
	public function process($filename, $section = '')
	{
		return bab_Template::printTemplate($this, $filename, $section);
	}


	/**
	 * Returns the php code to get the value of the property.
	 *
	 * This method is used during template parsing.
	 *
	 * @return string
	 */
	private static function value($templateObjectName, $propertyName)
	{
		return 'bab_Template::getValue(' . $templateObjectName . ', "' .  $propertyName . '")';
	}


	/**
	 * Returns the php code to get the value of the indexed array property.
	 *
	 * This method is used during template parsing.
	 *
	 * @return string
	 */
	private static function valueArray($templateObjectName, $propertyName, $indexValue)
	{
		return 'bab_Template::getValueArray(' . $templateObjectName . ', "' .  $propertyName . '", "' . $indexValue . '")';
	}


	/**
	 * This method is used during template parsing.
	 *
	 * @return string
	 */
	private static function lvalue($templateObjectName, $propertyName)
	{
		return 'bab_Template::getLValue(' . $templateObjectName . ', "' .  $propertyName . '")';
	}


	/**
	 * This method is used during template parsing.
	 *
	 * @return string
	 */
	private static function lvalueArray($templateObjectName, $propertyName, $indexValue)
	{
		return 'bab_Template::getLValueArray(' . $templateObjectName . ', "' .  $propertyName . '", "' . $indexValue . '")';
	}


	/**
	 * This method is used during template parsing.
	 *
	 * @return string
	 */
	private static function rvalue($templateObjectName, $propertyName)
	{
		return 'bab_Template::getRValue(' . $templateObjectName . ', "' .  $propertyName . '")';
	}


	/**
	 * This method is used during template execution.
	 *
	 * @return mixed
	 */
	private static function getRValue(&$templateObject, $propertyName)
	{
		return (@isset($templateObject->{$propertyName}) ? $templateObject->{$propertyName} : $propertyName);
	}


	/**
	 * This method is used during template execution.
	 *
	 * @return mixed
	 */
	private static function getLValue(&$templateObject, $propertyName)
	{
		if (@isset($templateObject->{$propertyName})) {
			return $templateObject->{$propertyName};
		}

		$tr = getGlobalVariable($propertyName);
		if (!is_null($tr)) {
			return $tr;
		}

		if (@isset($GLOBALS[$propertyName])) {
			return $GLOBALS[$propertyName];
		}
		return '';
	}


	/**
	 * This method is used during template execution.
	 *
	 * @return mixed
	 */
	private static function getLValueArray(&$templateObject, $propertyName, $index)
	{
		if (@isset($templateObject->{$propertyName}[$index])) {
			return $templateObject->{$propertyName}[$index];
		}

		if (NULL === @$templateObject->{$propertyName}[$index]) {
			return '';
		}

		$call = reset(debug_backtrace()); // $call will contain debug info about the line in the script where this function was called.
		bab_Template::addError($templateObject, 'Unknown property (' . $propertyName . '[' . $index . '])', $call['line']);
		return '';
	}


	/**
	 * This method is used during template execution.
	 *
	 * @return mixed
	 */
	private static function getValue(&$templateObject, $propertyName)
	{
		// We check if the property exists in the template object.
		if (@isset($templateObject->{$propertyName})) {
			return (string) $templateObject->{$propertyName};
		}

		// The property is not defined in the template object,
		// so we check if it is a white-listed global variable.
		$tr = getGlobalVariable($propertyName);
		if (!is_null($tr)) {
			$templateObject->{$propertyName} = $tr;
			return $tr;
		}

		$call = reset(debug_backtrace()); // $call will contain debug info about the line in the script where this function was called.
		bab_Template::addError($templateObject, 'Unknown property or global variable (' . $propertyName . ')', $call['line']);
		return '{ ' . $propertyName . ' }';
	}


	/**
	 * This method is used during template execution.
	 *
	 * @return mixed
	 */
	private static function getValueArray(&$templateObject, $propertyName, $index)
	{
		if (@isset($templateObject->{$propertyName}[$index])) {
			return $templateObject->{$propertyName}[$index];
		}
		$call = reset(debug_backtrace()); // $call will contain debug info about the line in the script where this function was called.
		bab_Template::addError($templateObject, 'Unknown property (' . $propertyName . '[' . $index . '])', $call['line']);
		return '{ ' . $propertyName . '[' . $index . '] }';
	}



	/**
	 * Parses an Ovidentia template string and returns the equivalent php code in a string.
	 * The code returned can be processed with a call to eval().
	 * @param	string $templateString		The Ovidentia template.
	 * @param	string $templateObjectName	The name of the template object that will be used
	 * 										in the php generated code.
	 * @return	string						The equivalent php code.
	 */
	private static function _parseTemplate($templateString, $templateObjectName)
	{
		$search = array('/<!--#if\s+(\w+)\s+-->/',
						'/<!--#if\s+(\w+)(?:\s+"(?:\s*(== |\!= |<= |>= |< |> )\s*([^"]*))")\s+-->/',
						'/<!--#if\s+(\w+)\[(\w+)\]\s+-->/',
						'/<!--#if\s+(\w+)\[(\w+)\](?:\s+"(?:\s*(== |\!= |<= |>= |< |> )\s*([^"]*))("))\s+-->/',
						'/<!--#else\s+(?:(?:[A-Za-z0-9_\[\]]+)\s+)?-->/',
						'/<!--#endif\s+(?:(?:[A-Za-z0-9_\[\]]+)\s+)?-->/',
						'/<!--#in\s+(\w+)\s+-->/',
						'/<!--#endin\s+(?:(?:\w+)\s+)?-->/',
						'/\{\s+\$OVML\(([^)]+)\)\s+\}/',
						'/\{\s+\$OVMLCACHE\(([^)]+)\)\s+\}/',
						'/\{\s+(\w+)\s+\}/',
						'/\{\s+(\w+)\[((?:\w+\s*)+)\]\s+\}/');
		$replace = array('<?php if (' . bab_Template::lvalue($templateObjectName, '$1') . '): ?>',
						 '<?php if (' . bab_Template::lvalue($templateObjectName, '$1') . ' $2 ' . bab_Template::rvalue($templateObjectName, '$3') . '): ?>',
						 '<?php if (' . bab_Template::lvalueArray($templateObjectName, '$1', '$2') . '): ?>',
						 '<?php if (' . bab_Template::lvalueArray($templateObjectName, '$1', '$2') . ' $3 ' . bab_Template::rvalue($templateObjectName, '$4') . '): ?>',
						 '<?php else: ?>',
						 '<?php endif; ?>',
						 '<?php $$1skip = false; while (' . $templateObjectName . '->$1($$1skip)): if ($$1skip) { $$1skip = false; continue; } ?>',
						 '<?php endwhile; ?>',
						 '<?php $params = explode(\',\', \'$1\'); $ovml = array_shift($params); $args = array(); foreach ($params as $param) { $tmp = explode(\'=\', $param); if (is_array($tmp) && count($tmp) == 2) { $var = trim($tmp[1], \'"\'); $var = isset(' . $templateObjectName . '->$var) ? ' . $templateObjectName . '->$var : $var; $args[trim($tmp[0])] = $var; } } print(bab_printOvmlTemplate($ovml, $args)); ?>',
						 '<?php $params = explode(\',\', \'$1\'); $ovml = array_shift($params); $args = array(); foreach ($params as $param) { $tmp = explode(\'=\', $param); if (is_array($tmp) && count($tmp) == 2) { $var = trim($tmp[1], \'"\'); $var = isset(' . $templateObjectName . '->$var) ? ' . $templateObjectName . '->$var : $var; $args[trim($tmp[0])] = $var; } } print(bab_printCachedOvmlTemplate($ovml, $args)); ?>',
						 '<?php @print(' . bab_Template::value($templateObjectName, '$1') . '); ?>',
						 '<?php @print(' . bab_Template::valueArray($templateObjectName, '$1', '$2') . '); ?>');
		$templatePhp = preg_replace($search, $replace, $templateString);
		return $templatePhp;
	}


	/**
	 * Returns an array of strings containing the names of the sections in the template file.
	 *
	 * @param	string	$pathname	The pathname of the template file.
	 * @return	array				The sections names.
	 */
	public static function getTemplates($pathname)
	{
	    $m = null;
		if (preg_match_all('/<\!--#begin\s+(.*?)\s+-->/', file_get_contents($pathname), $m)) {
			return $m[1];
		}
		return array();
	}
}

