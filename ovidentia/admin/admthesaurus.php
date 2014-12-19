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
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $babInstallPath."admin/acl.php";




/* main */
if( !bab_isUserAdministrator())
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'tags');

if( isset($tagsman) )
{
	maclGroups();
}

switch($idx)
	{
	case 'tags':
	default:
		$babBody->title = bab_translate("Thesaurus");
		$macl = new macl("admthesaurus", "tags", 1, "tagsman");
        $macl->addtable( BAB_TAGSMAN_GROUPS_TBL,bab_translate("Who can manage thesaurus?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("tags", bab_translate("Thesaurus"), $GLOBALS['babUrlScript']."?tg=topcats&idx=tags");
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminThesaurus');
?>