<?php
/************************************************************************
 * Ovidentia : add-on : Maintain Language File                          *
 ************************************************************************
 * Copyright (c) 2002, Koblix ( http://www.koblix.com )                 *
 ***********************************************************************/
function mtl_getAdminSectionMenus(&$url, &$text)
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

function mtl_getUserSectionMenus(&$url, &$text)
{
return false;
}

function mtl_onUserCreate( $id )
{
}

function mtl_onUserDelete( $id )
{
}

?>