<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/*
call to a function <!--var funcName --> defined funcName()
$f = "funcName";
$class->$f();
*/

class Template
{
var $startPatternI = "<!--#";
var $endPatternI = "-->";

//var $startPatternV = "<!--#var";
//var $endPatternV = "-->";

var $startPatternV = "{";
var $endPatternV = "}";

function printTemplate(&$class, $file, $section="")
	{
	$str = implode("", @file($file));

	if( !empty($section))
		{
		$section = preg_quote($section);
		$reg = "/".$this->startPatternI."begin\s+".$section."\s+".$this->endPatternI."(.*)".$this->startPatternI."end\s+".$section."\s+".$this->endPatternI."(.*)/s";
		$res = preg_match($reg, $str, $m);
		if( $res )
			$str = $m[1];
		}
	
	return $this->processTemplate($class, $str);
	}

function processTemplate(&$class, $str)
	{
	$reg = "/(.*?)".$this->startPatternI."(if|in)\s+(.*)/s";

	while($ret = preg_match($reg, $str, $m) > 0 )
		{
		if ($m[2] == "if")
			{
			$str = $this->processIf($class, $str);
			}
		if ($m[2] == "in")
			{
			$str = $this->processIn($class, $str);
			}
		}
	return $this->replaceVar($class, $str);
	}

function replaceVar(&$class, $str)
	{
	$reg = "/".$this->startPatternV."\s+(.*?)\s+".$this->endPatternV."/";
	preg_match_all($reg, $str, $m);

	for ($i = 0; $i < count($m[1]); $i++ )
		{
		$reg = "/".$this->startPatternV."\s+" . preg_quote($m[1][$i]). "\s+".$this->endPatternV."/";
		$reg2 = "/(.*?)\[([^\]]*)/";
		
		if( $ret = preg_match($reg2, $m[1][$i], $m2) > 0)
			{
			if( isset($class->{$m2[1]}[$m2[2]]))
				$str = preg_replace($reg, $class->{$m2[1]}[$m2[2]], $str);
			}
		else
			{
			if( isset($class->$m[1][$i]))
				$str = preg_replace($reg, $class->$m[1][$i], $str);
			}
		}
	return $str;
	}


function processIf(&$class, $str)
	{
	$reg = "/(.*?)".$this->startPatternI."if\s+(.*?)\s+".$this->endPatternI."/s";
	$res = preg_match($reg, $str, $m);
	if(!$res)
		return $str;

	$ret = "";
	$ret = chop($m[1]);
	
	$condition = $m[2];
	$reg = "/([^\"]*)\s+(.*)/s";
	$res = preg_match($reg, $m[2], $m0);

	if( $res )
		$var = $m0[1];
	else
		$var = $m[2];

	$var = trim($var);

	$reg = "/(.*?)\[([^\]]*)/";
	
	if( preg_match($reg, $var, $m2) > 0)
		{
		$barray = 1;
		//$str = preg_replace($reg, $class->{$m2[1]}[$m2[2]], $str);
		}

	if( $res )
		{
		$cond = $m0[2];
		$reg = "/\"\s*([^ ]*)\s+([^\"]*)\s*\"/s";
		$res = preg_match($reg, $cond, $match);
		if( isset($class->$match[2]))
			$val = $class->$match[2];
		else
			$val = $match[2];

		switch ($match[1])
			{
			case ">=":
				$bool = ($class->$var >= $val)?true:false;
				break;
			case "==":
				if( isset($barray))
					{
					$bool = ($class->{$m2[1]}[$m2[2]] == $val)?true:false;
					}
				else
					$bool = ($class->$var == $val)?true:false;
				break;
			case "!=":
				if( isset($barray))
					{
					$bool = ($class->{$m2[1]}[$m2[2]] != $val)?true:false;
					}
				else
					$bool = ($class->$var != $val)?true:false;
				break;
			case "<=":
				if( isset($barray))
					{
					$bool = ($class->{$m2[1]}[$m2[2]] <= $val)?true:false;
					}
				else
					$bool = ($class->$var <= $val)?true:false;
				break;
			case ">":
				if( isset($barray))
					{
					$bool = ($class->{$m2[1]}[$m2[2]] > $val)?true:false;
					}
				else
					{
					$bool = ($class->$var > $val)?true:false;
					}
				break;
			case "<":
				if( isset($barray))
					{
					$bool = ($class->{$m2[1]}[$m2[2]] < $val)?true:false;
					}
				else
					$bool = ($class->$var < $val)?true:false;
				break;
			default:
				if( isset($barray))
					{
					$bool = $class->{$m2[1]}[$m2[2]]?true:false;
					}
				else
					$bool = $class->$var?true:false;
				echo ("<BR>unknown operator : <B>" .$match[1]."</B></BR>\n");
				break;
			}
		}
	else
		{
		if( isset($barray))
			{
			$bool = $class->{$m2[1]}[$m2[2]]?true:false;
			}
		else
			$bool = $class->$var?true:false;
		}

	$reg = "/".$this->startPatternI."if\s+".preg_quote($m[2])."\s+".$this->endPatternI."(.*?)".$this->startPatternI."endif\s+".preg_quote($var)."\s+".$this->endPatternI."(.*)/s";
	$res = preg_match($reg, $str, $m2);
	if( !$res )
		die("<BR>if ".$m[2].".... endif : no matching </BR>");

	$reg = "/(.*)".$this->startPatternI."else\s+" . preg_quote($var) . "\s+".$this->endPatternI."(.*)/s";
	$res = preg_match($reg, $m2[1], $m3);

	if($res)
		{
		if($bool)
			$rep = $m3[1];
		else
			$rep = $m3[2];
		}
	else if ( $bool )
		$rep = $m2[1];

	if( $rep[strlen($rep)-1] == chr(10))
		$rep = substr($rep, 0, strlen($rep)-1);

	$ret = $ret . $rep .$m2[2];
	return $ret;
	}

function processIn(&$class, $str)
	{
	$reg = "/(.*?)".$this->startPatternI."in\s+(.*?)\s+".$this->endPatternI."/s";
	$res = preg_match($reg, $str, $m);
	if(!$res)
		return $str;

	$ret = "";
	$ret = $m[1];
	
	$reg = "/".$this->startPatternI."in\s+".$m[2]."\s+".$this->endPatternI."(.*?)".$this->startPatternI."endin\s+".$m[2]."\s+".$this->endPatternI."(.*)/s";
	$res = preg_match($reg, $str, $m2);
	if( !$res )
		die("<BR>in ".$m[2].".... endif ??? : no matching </BR>");

	$rep = trim($m2[1]);
	while( $class->$m[2]() )
		{
		$tmpstr = $rep;
		$tmpstr = $this->processTemplate($class, $tmpstr);
		$ret .= $this->replaceVar($class, $tmpstr);
		}

	$ret = $ret . $m2[2];
	return $ret;
	}
}

?>