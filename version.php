<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."version.inc";

function getVersion()
{
	$filename = "config.php";

	$file = @fopen($filename, "r");
	$txt = fread($file, filesize($filename));
	fclose($file);
	$reg = "babVersion[[:space:]]*=[[:space:]]*\"([^\"]*)\"";
	$res = ereg($reg, $txt, $match);
	return $match[1];
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
	while (false !== ($filename = readdir($handle)))
		{ 
		if ($filename != "." && $filename != "..")
			{
			if (is_dir($path.$filename))
				{
					$arr = array_merge($arr, echoLang($path.$filename."/"));
				}
			else
				{
				$file = @fopen($path.$filename, "r");
				$txt = fread($file, filesize($path.$filename));
				fclose($file);
				$reg = "/babTranslate[[:space:]]*\([[:space:]]*\"([^\"]*)/s";
				preg_match_all($reg, $txt, $m1);
				for ($i = 0; $i < count($m1[1]); $i++ )
					{
					if( !empty($m1[1][$i]) && !in_array($m1[1][$i], $arr) )
						$arr[] = $m1[1][$i];
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
		$oldversion = getVersion();
		if( $oldversion == $CurrentVersion)
		{
			$str = "Version ". $CurrentVersion . "<br>";
			$str .= babTranslate("You site is already up to date");
		}
		$oldversion = strtr($oldversion, ".", "-");
		include $babInstallPath."upgrade".$oldversion."to".strtr($CurrentVersion, ".", "-").".php";
		$str = upgrade();
		if( empty($str))
			putVersion($CurrentVersion);
		break;

	case "lang":
		$tab = array_unique(echoLang($GLOBALS['babInstallPath']));
		$filename = "lang/lang-".$GLOBALS['babLanguage'].".xml";
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
			$reg = "<string[[:space:]]*id=\"$tab[$i]\">([^<]*)<\/string>";
			if( !empty($tab[$i]))
				{
				if( !ereg($reg, $txt, $m))
					{
					$new .= "<string id=\"$tab[$i]\">".$tab[$i]."</string>"."\r\n";
					}
				else
					{
					$old .= "<string id=\"$tab[$i]\">".$m[1]."</string>"."\r\n";
					}
				}
			}
		$file = fopen($filename, "w");
		fputs($file, "<".$GLOBALS['babLanguage'].">\r\n".$old.$new."</".$GLOBALS['babLanguage'].">");
		fclose($file);
		$str = babTranslate("You language file has been updated") ."( ".$filename." )";
		break;


	case "version":
	default:
		$oldversion = getVersion();
		$str = "Source Version ". $CurrentVersion ."<br>";
		$str .= "Database Version ". $oldversion ."<br>";
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

