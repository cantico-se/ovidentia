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
include_once $babInstallPath."admin/acl.php";

/* main */
if( !$babBody->isSuperAdmin )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "groups";

if( isset($aclman) )
	{
	maclGroups();
	}

switch($idx)
	{
	default:
	case "groups":
		$babBody->title = bab_translate("Groups List");
		$macl = new macl("admstats", "groups", 1, "aclman");
        $macl->addtable( BAB_STATSMAN_GROUPS_TBL,bab_translate("Who can view statistics?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("stats", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=admstats&idx=groups");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>