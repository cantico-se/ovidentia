<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once "base.php";
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
			$str = preg_replace($reg, bab_printOvmlTemplate($m[1][$i]), $str);
			}
		}

	preg_match_all("/".$this->startPatternV."\s+(.*?)\s+".$this->endPatternV."/", $str, $m);

	for ($i = 0; $i < count($m[1]); $i++ )
		{
		$reg = "/".$this->startPatternV."\s+" . preg_quote($m[1][$i]). "\s+".$this->endPatternV."/";
		
		if( preg_match("/(.*?)\[([^\]]*)/", $m[1][$i], $m2) > 0)
			{
			if( isset($class->{$m2[1]}[$m2[2]]))
				$str = preg_replace($reg, $class->{$m2[1]}[$m2[2]], $str);
			}
		else
			{
			if( isset($class->$m[1][$i]))
				$str = preg_replace($reg, $class->$m[1][$i], $str);
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
	return $ret;;
	}
}

?>
