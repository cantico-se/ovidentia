<?php
function favorites_getAdminSectionMenus(&$url, &$text)
{
return false;
}

function favorites_getUserSectionMenus(&$url, &$text)
{
static $nbMenus=0;
if( !$nbMenus && !empty($GLOBALS['BAB_SESS_USERID']))
	{
	$url = $GLOBALS['babAddonUrl']."main";
	$text = bab_translate("Favorites", "favorites");
	$nbMenus++;
	return true;
	}
return false;
}


function favorites_onUserCreate( $id )
{
}

function favorites_onUserDelete( $id )
{
	$db = $GLOBALS['babDB'];
	$db->db_query("delete from favorites_list where id_owner='".$id."'");
}


function favorites_onGroupCreate( $id )
{
}

function favorites_onGroupDelete( $id )
{
}

function favorites_onSectionCreate()
{
return false;
}
?>
