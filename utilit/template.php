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

include_once 'base.php';


function &bab_TemplateCache_getStore()
{
	static $cacheStore = null;
	if ($cacheStore === null) {
		$cacheStore = array();
	}
	return $cacheStore;
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
	function get($filename, $section)
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
	function set($filename, $section, $parsedTemplate)
	{
		$cache =& bab_TemplateCache_getStore();
		$cache[$filename . '/' . $section] = $parsedTemplate;
	}
}


/**
 * This class implements the template engine of Ovidentia. It compiles the templates to
 * php and caches them in memory.
 * It can be used statically by calling bab_Template::printTemplate(), or it can be used
 * as the base class of a template and then be called on objects of this class, eg. $tpl->process();
 */
class bab_Template
{
	/**
	 * @param	string	$filename	The path to the template file.
	 * @param	string	$section	The section name in the template file.
	 * 								If empty, the whole template file is loaded.
	 * @return	string|null			The template content or null if not found.
	 * @access private
	 */
	function _loadTemplate($filename, $section)
	{
		if (!is_readable($filename)) {
			return null;
		}
		$templateString = implode('', @file($filename));
		if (!empty($section)) {
			$quotedSection = preg_quote($section);
			if (preg_match('/<!--#begin\s+' . $quotedSection . '\s+-->(.*)<!--#end\s+' . $quotedSection . '\s+-->(.*)/s', $templateString, $matches)) {
				$templateString = $matches[1];
			} else {
				return null;
			}
		}
		return $templateString;
	}
	
	/**
	 * Returns a string containing the processed template.
	 * @param	object	$template	The template object.
	 * @param	string	$filename	The path to the template file.
	 * @param	string	$section	The optional section name in the template file.
	 * 								If not specified or empty, the whole template file is
	 * 								processed.
	 * @return	string|null			The processed template or null.
	 * @access public
	 * @static
	 */
	function printTemplate(&$template, $filename, $section = '')
	{
		$parsedTemplate = bab_TemplateCache::get($filename, $section);
		if ($parsedTemplate === null) {
			$templateString = bab_Template::_loadTemplate($filename, $section);
			if ($templateString === null) {
				return null;
			}
			$parsedTemplate = bab_Template::_parseTemplate($templateString, '$template');
			bab_TemplateCache::set($filename, $section, $parsedTemplate);
		}
		ob_start();
		eval('?>' . $parsedTemplate);
		$processedTemplate = ob_get_contents();
		ob_end_clean();
		return $processedTemplate;
	}

	/**
	 * @param	string	$filename	The path to the template file.
	 * @param	string	$section	The optional section name in the template file.
	 * @access public
	 * @return	string
	 */
	function process($filename, $section = '')
	{
		return bab_Template::printTemplate($this, $filename, $section);
	}

	/**
	 * Parses an Ovidentia template string and returns the equivalent php code in a string.
	 * The code returned can be processed with a call to eval().
	 * @param	string $templateString		The Ovidentia template.
	 * @param	string $templateObjectName	The name of the template object that will be used
	 * 										in the php generated code.
	 * @return	string						The equivalent php code.
	 * @access private
	 * @static
	 */
	function _parseTemplate($templateString, $templateObjectName)
	{
		$search = array('/<!--#if\s+(\w+)(?:\s+"(?:(== |\!= |<= |>= |< |> )\s*([^"]+))("))?\s+-->/',
						'/<!--#if\s+(\w+)\[(\w+)\](?:\s+"(?:(== |\!= |<= |>= |< |> )\s*([^"]+))("))?\s+-->/',
						'/<!--#elseif\s+(\w+)(?:\s+"(?:(== |\!= |<= |>= |< |> )\s*([^"]+))("))?\s+-->/',
						'/<!--#else\s+(?:(?:\w+)\s+)?-->/',
						'/<!--#endif\s+(?:(?:\w+)\s+)?-->/',
						'/<!--#in\s+(\w+)\s+-->/',
						'/<!--#endin\s+(?:(?:\w+)\s+)?-->/',
						'/\{\s+\$OVML\(([^)]+)\)\s+\}/',
						'/\{\s+(\w+)\s+\}/',
						'/\{\s+(\w+)\[(\w+)\]\s+\}/');
		$replace = array('<?php if ((isset(' . $templateObjectName . '->$1) ? ' . $templateObjectName . '->$1 : (isset($GLOBALS["$1"]) ? $GLOBALS["$1"] : "")) $2$4$3$4): ?>',
						 '<?php if ((isset(' . $templateObjectName . '->$1["$2"]) ? ' . $templateObjectName . '->$1["$2"] : "")) $3$5$4$5): ?>',
						 '<?php elseif ((isset(' . $templateObjectName . '->$1) ? ' . $templateObjectName . '->$1 : (isset($GLOBALS["$1"]) ? $GLOBALS["$1"] : "")) $2$4$3$4): ?>',
						 '<?php else: ?>',
						 '<?php endif; ?>',
						 '<?php while (' . $templateObjectName . '->$1($skip = false)): if ($skip) continue; ?>',
						 '<?php endwhile; ?>',
						 '<?php $params = explode(\',\', \'$1\'); $ovml = array_shift($params); $args = array(); foreach ($params as $param) { $tmp = explode(\'=\', $param); if (is_array($tmp) && count($tmp) == 2) { $var = trim($tmp[1], \'"\'); $var = isset(' . $templateObjectName . '->$var) ? ' . $templateObjectName . '->$var : $var; $args[trim($tmp[0])] = $var; } } print(bab_printOvmlTemplate($ovml, $args)); ?>',
						 '<?php @print(isset(' . $templateObjectName . '->$1) ? ' . $templateObjectName . '->$1 : (isset($GLOBALS["$1"]) ? $GLOBALS["$1"] : "")); ?>',
						 '<?php isset(' . $templateObjectName . '->$1["$2"]) && @print(' . $templateObjectName . '->$1["$2"]); ?>');
		$templatePhp = preg_replace($search, $replace, $templateString);
		return $templatePhp;
	}

	/**
	 * Returns an array of strings containing the names of the sections in the template file.
	 * 
	 * @param	string	$filename	The path of the template file.
	 * @return	array				The sections names.
	 * @access public
	 * @static
	 */
	function getTemplates($filename)
	{
		if (preg_match_all('/<\!--#begin\s+(.*?)\s+-->/', implode('', @file($filename)), $m)) {
			return $m[1];
		}
		return array();
	}
}







/**
 * @deprecated 
 * @see bab_Template
 */
class babTemplate
{
var $startPatternI = "<!--#";
var $endPatternI = "-->";

var $startPatternV = "{";
var $endPatternV = "}";
var $crlf = "\r\n";
var $ophp = "<?php";
var $ephp = "?>";

function printTemplate(&$class, $file, $section="")
	{
	static $arrfiles = array();

	if( !isset($arrfiles[$file]))
		{
		if( !is_readable($file))
			{
			echo "Cannot read file ( Permission denied ): ". $file;
			die();
			}
		$arrfiles[$file] = implode("", @file($file));
		}

	if( !empty($section))
		{
		$section = preg_quote($section);	if(preg_match("/".$this->startPatternI."begin\s+".$section."\s+".$this->endPatternI."(.*)".$this->startPatternI."end\s+".$section."\s+".$this->endPatternI."(.*)/s", $arrfiles[$file], $m))
			return $this->processTemplate($class, $m[1]);
		else
			return "";
		}
	return $this->processTemplate($class, $arrfiles[$file]);
	}

function processTemplate(&$class, $str)
	{
	while( preg_match("/(.*?)".$this->startPatternI."(if|in)\s+(.*)/s", $str, $m) > 0 )
		{
		if ($m[2] == "if")
			{
			if(preg_match("/(.*?)".$this->startPatternI."if\s+(.*?)\s+".$this->endPatternI."/s", $str, $m))
				{
				$res = preg_match("/([^\"]*)\s+(.*)/s", $m[2], $m0);
				if( $res )
					$var = trim($m0[1]);
				else
					$var = trim($m[2]);

				if( preg_match("/(.*?)\[([^\]]*)/", $var, $m2) > 0)
					{
					if( isset($class->{$m2[1]}[$m2[2]]))
						$tvar = $class->{$m2[1]}[$m2[2]];
					else
						{
						if (!isset($$m2[1][$m2[2]])) $$m2[1][$m2[2]] = '';
						if (!isset($GLOBALS[$$m2[1][$m2[2]]])) $GLOBALS[$$m2[1][$m2[2]]] = '';
						$tvar = $GLOBALS[$$m2[1][$m2[2]]];
						}
					}
				else
					{
					if( isset($class->$var))
						$tvar = $class->$var;
					else
						{
						if (!isset($GLOBALS[$var])) $GLOBALS[$var] = '';
						$tvar = $GLOBALS[$var];
						}
					}

				if( $res )
					{
					preg_match("/\"\s*([^ ]*)\s+([^\"]*)\s*\"/s", $m0[2], $match);
					if( isset($class->$match[2]))
						$val = $class->$match[2];
					else
						$val = $match[2];

					switch ($match[1])
						{
						case ">=":
							$bool = ($tvar >= $val)?true:false;
							break;
						case "==":
							$bool = ($tvar == $val)?true:false;
							break;
						case "!=":
							$bool = ($tvar != $val)?true:false;
							break;
						case "<=":
							$bool = ($tvar <= $val)?true:false;
							break;
						case ">":
							$bool = ($tvar > $val)?true:false;
							break;
						case "<":
							$bool = ($tvar < $val)?true:false;
							break;
						default:
							$bool = $tvar?true:false;
							echo ("<BR>unknown operator : <B>" .$match[1]."</B></BR>\n");
							break;
						}
					}
				else
					{
					$bool = $tvar?true:false;
					}

				if(!preg_match("/".$this->startPatternI."if\s+".preg_quote($m[2])."\s+".$this->endPatternI."(.*?)".$this->startPatternI."endif\s+".preg_quote($var)."\s+".$this->endPatternI."(.*)/s", $str, $m2))
					die("<BR>if ".$m[2].".... endif : no matching </BR>");

				$rep = "";
				if(preg_match("/(.*)".$this->startPatternI."else\s+" . preg_quote($var) . "\s+".$this->endPatternI."(.*)/s", $m2[1], $m3))
					{
					if($bool)
						$rep = $m3[1];
					else
						$rep = $m3[2];
					}
				else if ( $bool )
					$rep = $m2[1];

				if( strlen($rep) > 1 && $rep[strlen($rep)-1] == chr(10))
					$rep = substr($rep, 0, strlen($rep)-1);

				$str =  chop($m[1]). $rep .$m2[2];
				}
			}
		if ($m[2] == "in")
			{
			if(preg_match("/(.*?)".$this->startPatternI."in\s+(.*?)\s+".$this->endPatternI."/s", $str, $m))
				{
				$ret = $m[1];
				if(!preg_match("/".$this->startPatternI."in\s+".$m[2]."\s+".$this->endPatternI."(.*?)".$this->startPatternI."endin\s+".$m[2]."\s+".$this->endPatternI."(.*)/s", $str, $m2) )
					die("<BR>in ".$m[2].".... endif ??? : no matching </BR>");
				$rep = trim($m2[1]);
				$skip = false;
				while( $class->$m[2]($skip) )
					{
					if( !$skip )
						$ret .= $this->processTemplate($class, $rep);
					$skip = false;
					}

				$str = $ret.$m2[2];
				}

			}
		}

	if( preg_match_all("/".$this->startPatternV."\s+\\\$OVML\((.*?)\)\s+".$this->endPatternV."/", $str, $m))
		{
		for ($i = 0; $i < count($m[1]); $i++ )
			{
			$reg = "/".$this->startPatternV."\s+\\\$OVML\(" . preg_quote($m[1][$i], "/"). "\)\s+".$this->endPatternV."/";
			$param = explode(',', $m[1][$i]);
			$args = array();
			if( ($cnt = count($param)) > 1 )
				{
					for( $i=1; $i < $cnt; $i++)
					{
						$tmp = explode('=', $param[$i]);
						if( is_array($tmp) && count($tmp) == 2 )
							{
							$var = trim($tmp[1], '"');
							$var = isset($class->$var)? $class->$var: $var;
							$args[trim($tmp[0])] = $var;
							}
					}
				}
			$str = preg_replace($reg, bab_printOvmlTemplate($param[0], $args), $str);
			}
		}

	preg_match_all("/".$this->startPatternV."\s+(.*?)\s+".$this->endPatternV."/", $str, $m);

	for ($i = 0; $i < count($m[1]); $i++ )
		{
		$reg = "/".$this->startPatternV."\s+" . preg_quote($m[1][$i]). "\s+".$this->endPatternV."/";
		
		if( preg_match("/(.*?)\[([^\]]*)/", $m[1][$i], $m2) > 0)
			{
			if( isset($class->{$m2[1]}[$m2[2]]))
				{
				$tmp = $class->{$m2[1]}[$m2[2]];
				$str = preg_replace($reg, preg_replace("/\\$[0-9]/", "\\\\$0", $tmp), $str);
				}
			}
		else
			{
			if( isset($class->$m[1][$i]))
				{
				$tmp = $class->$m[1][$i];
				$str = preg_replace($reg, preg_replace("/\\$[0-9]/", "\\\\$0", $tmp) , $str);
				}
			else if( isset($GLOBALS[$m[1][$i]]))
				$str = preg_replace($reg, $GLOBALS[$m[1][$i]], $str);
			}
		}
	return $str;
	}

function getTemplates($file)
	{
	$ret = array();
	if(preg_match_all("/".$this->startPatternI."begin\s+(.*?)\s+".$this->endPatternI."/", implode("", @file($file)), $m))
		{
		for( $i = 0; $i < count($m[1]); $i++ )
			$ret[] = $m[1][$i];

		}
	return $ret;
	}
}

?>
