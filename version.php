<?php
$CurrentVersion = "2.3";

function getVersion()
{
	$filename = "config.php";

	$file = @fopen($filename, "r");
	$txt = fread($file, filesize($filename));
	fclose($file);
	$reg = "/babVersion\s*=\s*\"([^ ]*)\"/s";
	$res = preg_match($reg, $txt, $match);

	return $match[1];
}

function putVersion($version)
{
	$filename = "config.php";

	$file = @fopen($filename, "r");
	$txt = fread($file, filesize($filename));
	fclose($file);
	$reg = "/babVersion\s*=\s*\"([^ ]*)\"/s";
	$res = preg_match($reg, $txt, $match);

	$reg = "/babVersion\s*=\s*\"".$match[1]."\"/s";
	$out = preg_replace($reg, "babVersion = \"".$version."\"", $txt);
	$file = fopen($filename, "w");
	fputs($file, $out);
	fclose($file);
	return $match[1];
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
		include "upgrade".$oldversion."to".strtr($CurrentVersion, ".", "-");
		$str = upgrade();
		if( empty($str))
			putVersion(CurrentVersion);
		break;

	case "version":
	default:
		$str = "Version ". $CurrentVersion;
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

