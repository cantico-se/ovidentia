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

function cleanStatsTables()
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function temp()
			{
			$this->message = bab_translate("Are you sure you want to clean statistics logs");
			$this->title = '';
			$this->warning = bab_translate("WARNING: This operation will delete all statistics records"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admstats&idx=delete&action=yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admstats&idx=man";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function confirmCleanStatTables()
{
	global $babDB;
	$babDB->db_query("truncate table `".BAB_STATS_EVENTS_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_ADDONS_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_ARTICLES_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_ARTICLES_REF_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_FAQQRS_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_FAQS_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_FMFILES_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_FMFOLDERS_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_FORUMS_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_MODULES_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_OVML_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_PAGES_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_POSTS_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_SEARCH_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_THREADS_TBL."`");
	$babDB->db_query("truncate table `".BAB_STATS_XLINKS_TBL."`");
}

/* main */
if( !$babBody->isSuperAdmin )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}


if( !isset($idx))
	$idx = "man";

if( isset($aclman) )
	{
	maclGroups();
	}

switch($idx)
	{
	case "empty":
		$babBody->title = bab_translate("Clean statistics logs");
		cleanStatsTables();
		$babBody->addItemMenu("man", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admstats&idx=man");
		$babBody->addItemMenu("empty", bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=admstats&idx=empty");
		break;
	case "delete":
		if( isset($action) && $action == 'yes' )
		{
			confirmCleanStatTables();
			$babBody->msgerror = bab_translate("Done");
			$babBody->addItemMenu("man", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admstats&idx=man");
			$babBody->addItemMenu("delete", bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=admstats&idx=empty");
			break;
		}
		else
		{
		$idx = 'man';
		/* no break */
		}
	default:
	case "man":
		$babBody->title = bab_translate("Groups List");
		$macl = new macl("admstats", "groups", 1, "aclman");
        $macl->addtable( BAB_STATSMAN_GROUPS_TBL,bab_translate("Who can view statistics?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("man", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admstats&idx=man");
		$babBody->addItemMenu("empty", bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=admstats&idx=empty");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>