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
include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/upgradeincl.php';


function getVersion()
{
	$ini = new bab_inifile();
	$ini->inifile($GLOBALS['babInstallPath'].'version.inc');

	$str = "Sources Version ". $ini->getVersion()."\n";
	$db = &$GLOBALS['babDB'];

	$dbver = array();
	$res = $db->db_query("select foption, fvalue from ".BAB_INI_TBL." where foption IN('ver_major', 'ver_minor', 'ver_build')");
	if (3 === $db->db_num_rows($res)) {
		while ($rr = $db->db_fetch_array($res)) {
			$dbver[$rr['foption']] = $rr['fvalue'];
		}
		
		$str .= "Database Version ". $dbver['ver_major'].".".$dbver['ver_minor'].".".$dbver['ver_build'] ."\n";
	} else {
		$str .= "No Database Version (installation is not complete)\n";
	}
	return $str;
}



function echoLang($path)
{
	set_time_limit(3600);
	
	$arr = array();
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
$idx = bab_rp('idx','version');


$str = "";
switch($idx)
	{
	case "upgrade":
		bab_upgrade($GLOBALS['babInstallPath'], $str);
		break;

	case "addons":
		if( !bab_isUserAdministrator())
			die(bab_translate("You must be logged as administrator"));

		if (isset($_GET['from']))
			{
			bab_cpaddons($_GET['from'], bab_rp('to',$GLOBALS['babInstallPath']), $str);
			}
		break;

	case "lang":
		if( !bab_isUserAdministrator())
			exit;
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
		if( !bab_isUserAdministrator())
			exit;
		$str = getVersion();
		break;
	}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Ovidentia</TITLE>
<META NAME="Generator" CONTENT="Ovidentia">
<META NAME="Author" CONTENT="Cantico">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
</HEAD>
<BODY BGCOLOR="#FFFFFF">
<center><h1>Ovidentia</h1>
<?php
echo $babSiteName . "<br>";
echo bab_toHtml($str, BAB_HTML_ALL);
?>
<br>
<p class="copyright">&copy; 2001, <a href="http://www.cantico.fr/">CANTICO</a> All rights reserved.</p></center>
</BODY>
</HTML>

