<?php
function openwebmail_getAdminSectionMenus(&$url, &$text)
{
	static $i=0;
	if( $i )
	{
		return false;
	}
	else
	{
		require_once( $GLOBALS['babAddonPhpPath']."owincl.php");
		$url = $GLOBALS['babAddonUrl']."admin";
		$text = ow_translate("Openwebmail");
		$i++;
		return true;
	}
}

function openwebmail_getUserSectionMenus(&$url, &$text)
{
	static $i=0;
	if( $i )
	{
		return false;
	}
	else
	{
		require_once( $GLOBALS['babAddonPhpPath']."owincl.php");
		$url = $GLOBALS['babAddonUrl']."main";
		//$url = "javascript:Start('".$GLOBALS['babAddonUrl']."main&idx=openp', 'Openwebmail', '');";
		$text = ow_translate("Openwebmail");
		$i++;
		return true;
	}
}

function openwebmail_onUserCreate( $id )
{
}

function openwebmail_onUserDelete( $id )
{
}

function openwebmail_onGroupCreate( $id )
{
}

function openwebmail_onGroupDelete( $id )
{
}

function openwebmail_onSectionCreate( &$title, &$content)
{
	return false;
}
?>