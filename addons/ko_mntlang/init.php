<?php
/************************************************************************
 * Ovidentia : add-on : Maintain Language File                          *
 ************************************************************************
 * Copyright (c) 2002, Koblix ( http://www.koblix.com )                 *
 ***********************************************************************/
function ko_mntlang_getAdminSectionMenus(&$url, &$text)
{
static $nbMenus=0;
if( !$nbMenus )
	{
		$url = $GLOBALS['babAddonUrl']."mainlang";
		$text = "Language";
		$nbMenus++;
		return true;
	}
return false;
}

function ko_mntlang_getUserSectionMenus(&$url, &$text)
{
return false;
}

function ko_mntlang_onUserCreate( $id )
{
}

function ko_mntlang_onUserDelete( $id )
{
}

?>