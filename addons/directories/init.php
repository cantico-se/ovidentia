<?php
/*
CREATE TABLE `ad_directories` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
`name` VARCHAR(255) NOT NULL, 
`description` VARCHAR(255) NOT NULL, 
`ldap` ENUM('N','Y') NOT NULL, 
`host` TINYTEXT NOT NULL, 
`basedn` TEXT NOT NULL, 
`userdn` TEXT NOT NULL, 
`password` TINYBLOB NOT NULL
); 
*/
function directories_getAdminSectionMenus(&$url, &$text)
{
	static $i=0;
	if( $i || ( $GLOBALS['BAB_SESS_NICKNAME'] != "nouaya" && $GLOBALS['BAB_SESS_NICKNAME'] != "jlb") )
	{
		return false;
	}
	else
	{
		require_once( $GLOBALS['babAddonPhpPath']."adincl.php");
		$url = $GLOBALS['babAddonUrl']."admin";
		$text = ad_translate("Directories");
		$i++;
		return true;
	}
}

function directories_getUserSectionMenus(&$url, &$text)
{
	static $i=0;
	if( $i || ( $GLOBALS['BAB_SESS_NICKNAME'] != "nouaya" && $GLOBALS['BAB_SESS_NICKNAME'] != "jlb"))
	{
		return false;
	}
	else
	{
		require_once( $GLOBALS['babAddonPhpPath']."adincl.php");
		$url = $GLOBALS['babAddonUrl']."main";
		$text = ad_translate("Directories");
		$i++;
		return true;
	}
}

function directories_onUserCreate( $id )
{
}

function ad_onUserDelete( $id )
{
}

function directories_onGroupCreate( $id )
{
}

function ad_onGroupDelete( $id )
{
}

function directories_onSectionCreate( &$title, &$content)
{
	$title = "Ma section";
	$content = "<center><b>Nico! Ca marche!</b><br>";
	$content .= "Reste la doc à faire :-(</center>";
	return true;
}
?>