<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
if( !empty($_SERVER['PHP_SELF']))
	$babPhpSelf = $_SERVER['PHP_SELF'];
else
	$babPhpSelf = $HTTP_SERVER_VARS['PHP_SELF'];

if ($babPhpSelf != "/index.php")
	{
	if( !empty($_SERVER['HTTP_HOST']))
		Header("Location: http://". $_SERVER['HTTP_HOST']."/index.php?tg=accden");
	else
		Header("Location: http://". $HTTP_SERVER_VARS['HTTP_HOST']."/index.php?tg=accden");
	exit;
	}
?>