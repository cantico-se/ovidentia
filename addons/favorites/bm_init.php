<?php
/*
CREATE TABLE `bm_list` (
`id` INT(11) UNSIGNED NOT NULL, 
`id_owner` INT(11) UNSIGNED NOT NULL, 
`url` TINYTEXT NOT NULL, 
`description` TEXT NOT NULL,
PRIMARY KEY (`id`)
); 
*/
function bm_getAdminSectionMenus(&$url, &$text)
{
	return false;
}

function bm_getUserSectionMenus(&$url, &$text)
{
	static $nbMenus=0;
	if( !$nbMenus && !empty($GLOBALS['BAB_SESS_USERID']))
	{
		$url = $GLOBALS['babAddonUrl']."main";
		$text = "Favorites";
		$nbMenus++;
		return true;
	}
	return false;
}

function bm_onUserCreate( $id )
{
}

function bm_onUserDelete( $id )
{
	$db = $GLOBALS['babDB'];
	$db->db_query("delete from bm_list where id_owner='".$id."'");
}
?>