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
		$oldversion = getVersion();
		if( $oldversion == $CurrentVersion)
		{
			$str = "Version ". $CurrentVersion . "<br>";
			$str .= bab_translate("You site is already up to date");
		}
		$oldversion = strtr($oldversion, ".", "-");
		include $babInstallPath."upgrade".$oldversion."to".strtr($CurrentVersion, ".", "-").".php";
		$str = upgrade();
		if( empty($str))
			putVersion($CurrentVersion);
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
				else
					{
					$old .= "<string id=\"$tab[$i]\">".$m[1]."</string>"."\r\n";
					}
				}
			}
		$file = @fopen($filename, "w");
		if( $file )
			{
			fputs($file, "<".$cmd.">\r\n".$old.$new."</".$cmd.">");
			fclose($file);
			$str = bab_translate("You language file has been updated") ."( ".$filename." )";
			}
		else
			$str = bab_translate("Cannot open file for writing") ."( ".$filename." )";
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

