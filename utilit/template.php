<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
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

function compileTemplate($content,$file)
	{
	$ret = "";
	$ret .= $this->ophp.$this->crlf;

	$func = basename(strval($file),".html");
	if(!preg_match_all("/".$this->startPatternI."begin\s+(.*?)\s+".$this->endPatternI."(.*?)".$this->startPatternI."end\s+(.*?)\s+".$this->endPatternI."/s", $content, $m))
		{
		$m[1][0] = "";
		$m[2][0] = $content;
		}
	for ($i = 0; $i < count($m[1]); $i++ )
		{
		$ret .= "function ".$func."_".str_replace(" ", "", str_replace(".", "", $m[1][$i]))."(&\$class)".$this->crlf;
		$ret .= "{".$this->crlf;
		$ret .= "\$babret = <<<CONTENT".$this->crlf;

		preg_match_all("/".$this->startPatternI."(if|else|endif|in|endin)\s+(.*?)".$this->endPatternI."/", $m[2][$i], $m2);
		for( $h=0; $h < count($m2[1]); $h++)
			{
			$m2[2][$h] = trim($m2[2][$h]);
			switch($m2[1][$h])
				{
				case "if":
					$res = preg_match("/([^\"]*)\s+(.*)/s", $m2[2][$h], $m0);
					if( $res )
						$var = trim($m0[1]);
					else
						$var = trim($m2[2][$h]);

					$val = "";
					$op = "";

					if( $res )
						{
						preg_match("/\"\s*([^ ]*)\s+([^\"]*)\s*\"/s", $m0[2], $match);
						$val = $match[2];
						$op = $match[1];
						if( !is_numeric($val))
							$val = "\"".$val."\"";
						}

					if( preg_match("/(.*?)\[([^\]]*)/", $var, $m4) > 0)
						{
						if( $res )
							$repl = $this->crlf."CONTENT;".$this->crlf."if( (isset(\$class->".$m4[1]."['".$m4[2]."']". ") && \$class->".$m4[1]."['".$m4[2]."']".$op.$val.") || (isset(\$GLOBALS[\$".$m4[1]."['".$m4[2]."']]". ") && \$GLOBALS[\$".$m4[1]."['".$m4[2]."']]".$op.$val.")) {".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
						else
							$repl = $this->crlf."CONTENT;".$this->crlf."if( (isset(\$class->".$m4[1]."['".$m4[2]."']". ") && \$class->".$m4[1]."['".$m4[2]."']".") || (isset(\$GLOBALS[\$".$m4[1]."['".$m4[2]."']]". ") && \$GLOBALS[\$".$m4[1]."['".$m4[2]."']]".")) {".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
						}
					else
						{
						if( $res )
							$repl = $this->crlf."CONTENT;".$this->crlf."if( (isset(\$class->".$var.") && \$class->".$var.$op.$val.") || (isset(\$GLOBALS[\$".$var."]". ") && \$GLOBALS[\$".$var."]".$op.$val.")) {".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
						else
							$repl = $this->crlf."CONTENT;".$this->crlf."if( (isset(\$class->".$var.") && \$class->".$var.") || (isset(\$GLOBALS[\$".$var."]". ") && \$GLOBALS[\$".$var."]".")) {".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
						}
					$reg = "/".$this->startPatternI."if\s+".preg_quote($m2[2][$h])."\s+".$this->endPatternI."/";
					$m[2][$i] = preg_replace($reg, $repl, $m[2][$i]);
					break;
				case "else":
					$reg = "/".$this->startPatternI."else\s+".preg_quote($m2[2][$h])."\s+".$this->endPatternI."/";
					$repl = $this->crlf."CONTENT;".$this->crlf." } else {".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
					$m[2][$i] = preg_replace($reg, $repl, $m[2][$i]);
					break;
				case "endif":
					$reg = "/".$this->startPatternI."endif\s+".preg_quote($m2[2][$h])."\s+".$this->endPatternI."/";
					$repl = $this->crlf."CONTENT;".$this->crlf." }".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
					$m[2][$i] = preg_replace($reg, $repl, $m[2][$i]);
					break;
				case "in":
					$reg = "/".$this->startPatternI."in\s+".preg_quote($m2[2][$h])."\s+".$this->endPatternI."/";
					$repl = $this->crlf."CONTENT;".$this->crlf."while(\$class->".$m2[2][$h]."()) {".$this->crlf;
					$repl .= "\$babret .= <<<CONTENT".$this->crlf;
					$m[2][$i] = preg_replace($reg, $repl, $m[2][$i]);
					break;
				case "endin":
					$reg = "/".$this->startPatternI."endin\s+".preg_quote($m2[2][$h])."\s+".$this->endPatternI."/";
					$repl = $this->crlf."CONTENT;".$this->crlf." }".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
					$m[2][$i] = preg_replace($reg, $repl, $m[2][$i]);
					break;
				default:
					break;
				}
			}

		preg_match_all("/".$this->startPatternV."\s+(.*?)\s+".$this->endPatternV."/", $m[2][$i], $m2);

		for ($k = 0; $k < count($m2[1]); $k++ )
			{
			if( !ereg("( |;|\.)", $m2[1][$k] ))
				{
				$reg = "/".$this->startPatternV."\s+" . preg_quote($m2[1][$k]). "\s+".$this->endPatternV."/";
				if( preg_match("/(.*?)\[([^\]]*)/", $m2[1][$k], $m3) > 0)
					{
					$repl = $this->crlf."CONTENT;".$this->crlf."\$babret .= \$class->".$m3[1]."['".$m3[2]."'];".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
					}
				else
					{
					$repl = $this->crlf."CONTENT;".$this->crlf."if( isset(\$class->".$m2[1][$k]. ")) \$babret .= \$class->".$m2[1][$k]."; else  if( isset(\$GLOBALS['".$m2[1][$k]. "'])) \$babret .= \$GLOBALS['".$m2[1][$k]. "'];".$this->crlf."\$babret .= <<<CONTENT".$this->crlf;
					}
				$m[2][$i] = preg_replace($reg, $repl, $m[2][$i]);
				}
			}

		$ret .=  $m[2][$i];
		$ret .= $this->crlf."CONTENT;".$this->crlf;
		$ret .= "return \$babret;".$this->crlf;
		$ret .= "}".$this->crlf;
		}	
	$ret .= $this->ephp.$this->crlf;
	$fd = fopen($file.".php", "w");
	if( $fd )
		{
		fputs($fd, $ret);
		fclose($fd);
		}
	}

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
						$tvar = $GLOBALS[$$m2[1][$m2[2]]];
					}
				else
					{
					if( isset($class->$var))
						$tvar = $class->$var;
					else
						$tvar = $GLOBALS[$var];
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
				while( $class->$m[2]() )
					{
					$ret .= $this->processTemplate($class, $rep);
					}

				$str = $ret.$m2[2];
				}

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
}

?>
