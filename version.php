<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."version.inc";

function bab_array_search($str, $vars)
{
	foreach ($vars as $key => $val)
	{
		if ($val == $str)
		{
			return $key;
		}
	}
	return false;
}

function upgrade()
{
$bab_versions = array("310", "320", "330", "331", "332");

$ret = "";
$db = $GLOBALS['babDB'];

$res = $db->db_query("show tables like '".BAB_INI_TBL."'");
if( !$res || $db->db_num_rows($res) < 1)
	{
	$dbver = explode(".", $GLOBALS['babVersion']);
	$dbver[2] = "0";
	}
else
	{
	$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_major'"));
	$dbver[] = $rr['fvalue'];
	$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_minor'"));
	$dbver[] = $rr['fvalue'];
	$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_build'"));
	$dbver[] = $rr['fvalue'];
	}

$ver_from = $dbver[0].$dbver[1].$dbver[2];
$ver_to = $GLOBALS['bab_ver_major'].$GLOBALS['bab_ver_minor'].$GLOBALS['bab_ver_build'];
if( $ver_from == $ver_to )
	{
	return bab_translate("You site is already up to date");
	}

$i_from = bab_array_search($ver_from, $bab_versions);
$i_to = bab_array_search($ver_to, $bab_versions);

include $GLOBALS['babInstallPath']."upgrade.php";
for( $i = $i_from; $i < $i_to; $i++)
	{
	$func = "upgrade".$bab_versions[$i]."to".$bab_versions[$i+1];
	if( function_exists($func))
		{
		$ret = $func();
		if( !empty($ret))
			return $ret;
		}
	else
		{
		$ret .= "Call to undefined function: ".$func."()<br>";
		return $ret;
		}
	}
$db->db_query("update ".BAB_INI_TBL." set fvalue='".$GLOBALS['bab_ver_major']."' where foption='ver_major'");
$db->db_query("update ".BAB_INI_TBL." set fvalue='".$GLOBALS['bab_ver_minor']."' where foption='ver_minor'");
$db->db_query("update ".BAB_INI_TBL." set fvalue='".$GLOBALS['bab_ver_build']."' where foption='ver_build'");
putVersion($GLOBALS['bab_ver_major'].".".$GLOBALS['bab_ver_minor']);
$ret .= bab_translate("You site has been updated")."<br>";
$ret .= "From ". $dbver[0].".".$dbver[1].".".$dbver[2] ." to ". $GLOBALS['bab_ver_major'].".".$GLOBALS['bab_ver_minor'].".".$GLOBALS['bab_ver_build'];
return $ret;
}

function getVersion()
{
	$str = "Sources Version ". $GLOBALS['bab_ver_major'].".".$GLOBALS['bab_ver_minor'].".".$GLOBALS['bab_ver_build']."<br>";
	$db = $GLOBALS['babDB'];

	$res = $db->db_query("show tables like '".BAB_INI_TBL."'");
	if( !$res || $db->db_num_rows($res) < 1)
		{
		$dbver = explode(".", $GLOBALS['babVersion']);
		$dbver[2] = "0";
		}
	else
		{
		$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_major'"));
		$dbver[] = $rr['fvalue'];
		$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_minor'"));
		$dbver[] = $rr['fvalue'];
		$rr = $db->db_fetch_array($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_build'"));
		$dbver[] = $rr['fvalue'];
		}
	$str .= "Database Version ". $dbver[0].".".$dbver[1].".".$dbver[2] ."<br>";
	return $str;
}

function putVersion($version)
{
	$filename = "config.php";

	$file = @fopen($filename, "r");
	$txt = fread($file, filesize($filename));
	fclose($file);
	$reg = "babVersion[[:space:]]*=[[:space:]]*\"([^\"]*)\"";
	$res = ereg($reg, $txt, $match);

	$reg = "babVersion[[:space:]]*=[[:space:]]*\"".$match[1]."\"";
	$out = ereg_replace($reg, "babVersion = \"".$version."\"", $txt);
	$file = fopen($filename, "w");
	fputs($file, $out);
	fclose($file);
	return $match[1];
}

function echoLang($path)
{
	$arr = array();
	if( empty($GLOBALS['babLanguage']))
		return $arr;
	$handle = opendir($path); 
	while (false != ($filename = readdir($handle)))
		{ 
		if ($filename != "." && $filename != "..")
			{
			if (($filename == "utilit" || $filename == "admin") && is_dir($path.$filename))
				{
					$arr = array_merge($arr, echoLang($path.$filename."/"));
				}
			else
				{
				if( substr($filename,-4) == ".php")
					{
					$file = fopen($path.$filename, "r");
					if( $file )
						{
						$txt = fread($file, filesize($path.$filename));
						fclose($file);
						$reg = "/bab_translate[[:space:]]*\([[:space:]]*\"([^\"]*)/s";
						preg_match_all($reg, $txt, $m1);
						for ($i = 0; $i < count($m1[1]); $i++ )
							{
							if( !empty($m1[1][$i]) && !in_array($m1[1][$i], $arr) )
								{
								$arr[] = $m1[1][$i];
								}
							}
						}
					}
				}
			} 
		}
	closedir($handle);
	return $arr;
}

/* main */
if( !isset($idx))
	$idx = "version";

$str = "";
switch($idx)
	{
	case "upgrade";
		$str = upgrade();
		break;

	case "lang":
		$ar = echoLang($GLOBALS['babInstallPath']);
		$tab = array();
		for( $i = 0; $i < count($ar); $i++)
			{
			if( in_array($ar[$i], $tab) == false )
				$tab[] = $ar[$i];
			}
		if( !isset($cmd) || empty($cmd))
			$cmd = $GLOBALS['babLanguage'];
		$filename = $GLOBALS['babInstallPath']."lang/lang-".$cmd.".xml";
		if( !file_exists($filename))
			{
			$txt = "";
			}
		else
			{
			$file = @fopen($filename, "r");
			$txt = fread($file, filesize($filename));
			fclose($file);
			}
		$old = "";
		$new = "";
		for( $i = 0; $i < count($tab); $i++)
			{
			$reg = "<string[[:space:]]*id=\"".preg_quote($tab[$i])."\">([^<]*)<\/string>";
			if( !empty($tab[$i]))
				{
				if( !ereg($reg, $txt, $m))
					{
					$new .= "<string id=\"$tab[$i]\">".$tab[$i]."</string>"."\r\n";
					}
				}
			}
		$file = @fopen($filename, "w");
		if( $file )
			{
			$reg = "/<".$cmd.">(.*)<\/".$cmd.">/s";
			preg_match($reg, $txt, $m);
			$txt = "<".$cmd.">".$m[1].$new."</".$cmd.">";
			fputs($file, $txt);
			fclose($file);
			$str = bab_translate("You language file has been updated") ."( ".$filename." )";
			}
		else
			$str = bab_translate("Cannot open file for writing") ."( ".$filename." )";
		break;


	case "version":
	default:
		$str = getVersion();
		break;
	}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> New Document </TITLE>
<META NAME="Generator" CONTENT="Ovidentia">
<META NAME="Author" CONTENT="Cantico">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
</HEAD>
<BODY BGCOLOR="#FFFFFF">
<center><H1>Ovidentia</H1>
<?php
echo $babSiteName . "<br>";
echo $str;
?>
<br>
<P class="copyright">&copy; 2001, <a href="http://www.cantico.fr/">CANTICO</a> All rights reserved.</P></center>
</BODY>
</HTML>

