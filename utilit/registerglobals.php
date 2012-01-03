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
/**
* @internal SEC1 NA 05/12/2006 FULL
*/
include_once 'base.php';

/**
 * Security : destroy primary globals variables of Ovidentia (function used in index.php)
 * to avoid the modification of global variables by GET, POST...
 *
 * @param $arr Array
 */
function bab_unset(&$arr)
{
	unset($arr['babInstallPath'], $arr['babDBHost'], $arr['babDBLogin'], $arr['babDBPasswd'], $arr['babDBName']);
	unset($arr['babUrl'], $arr['babFileNameTranslation'], $arr['babVersion']);
}


/*
 * The old code of Ovidentia used PHP configuration register_globals to On.
* To remain compatible, we add all received data as globals variables.
* Security : primary globals variables of Ovidentia are destroyed
*/


if (!empty($_GET)) {
	bab_unset($_GET);
	foreach($_GET as $param => $value)
	{
		if (!isset($GLOBALS[$param]))
		{
			$GLOBALS[$param] = $value;
		}
	}
}

if (!empty($_POST)) {
	bab_unset($_POST);
	foreach($_POST as $param => $value)
	{
		if (!isset($GLOBALS[$param]))
		{
			$GLOBALS[$param] = $value;
		}
	}
}

bab_unset($_REQUEST);
bab_unset($_COOKIE);