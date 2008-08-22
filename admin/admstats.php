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
/*
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
			$this->warning = bab_translate("WARNING: This operation will delete all statistics records!");
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admstats&idx=delete&action=yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admstats&idx=man";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}
*/

function cleanStatsTables()
	{
	global $babBody;
	
	class temp
		{
		var $t_statitem;

		var $t_delete_statitems_before;
		var $t_save;

		function temp()
			{
			$this->t_delete_statitems_before = bab_translate("Delete logs before (dd/mm/yyyy)");
			$this->t_save = bab_translate("Ok");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"admstats.html", "cleanstats"));
	}

function confirmCleanStatTables($deleteBefore = null)
{
	global $babDB;

	if (!is_null($deleteBefore)) {

		$before = $deleteBefore->getIsoDate();
		if( $before >= date('Y-m-d') )
		{
		$babDB->db_query("truncate table `".BAB_STATS_EVENTS_TBL."`");
		$babDB->db_query("truncate table `".BAB_STATS_ADDONS_TBL."`");
		$babDB->db_query("truncate table `".BAB_STATS_ARTICLES_TBL."`");
		$babDB->db_query("truncate table `".BAB_STATS_ARTICLES_REF_TBL."`");
		$babDB->db_query("truncate table `".BAB_STATS_ARTICLES_NEW_TBL."`");
		$babDB->db_query("truncate table `".BAB_STATS_FAQQRS_TBL."`");
		$babDB->db_query("truncate table `".BAB_STATS_FAQS_TBL."`");
		$babDB->db_query("truncate table `".BAB_STATS_FMFILES_TBL."`");
		$babDB->db_query("truncate table `".BAB_STATS_FMFILES_NEW_TBL."`");
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
		else
		{
		$babDB->db_query('delete from `'.BAB_STATS_EVENTS_TBL.'` where evt_time < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_ADDONS_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_ARTICLES_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_ARTICLES_NEW_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_FAQQRS_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_FAQS_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_FMFILES_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_FMFILES_NEW_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_FMFOLDERS_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_FORUMS_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_MODULES_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_OVML_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_PAGES_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_POSTS_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_SEARCH_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_THREADS_TBL.'` where st_date < '.$babDB->quote($before));
		$babDB->db_query('delete from `'.BAB_STATS_XLINKS_TBL.'` where st_date < '.$babDB->quote($before));
		}
		return true;
	}
	else
	{
		return false;
	}
	
}


function editConnectionLogSettings()
{
	global $babBody;
	class ConnectionLoggingSetupTemplate
	{
		var $t_log_activated;
		var $t_log_deactivated;

		var $t_save_user_connection_history;
		var $t_activate;
		var $t_deactivate;
		var $t_delete_logs_before;
		var $t_save;
		
		function ConnectionLoggingSetupTemplate()
		{
			$registry = bab_getRegistryInstance();
			$registry->changeDirectory('/bab/statistics');
			$this->t_log_activated = $registry->getValue('logConnections', false);
			$this->t_log_deactivated = !$this->t_log_activated;
			
			$this->t_save_user_connection_history = bab_translate("Users connections history:");
			$this->t_activate = bab_translate("Enabled");
			$this->t_deactivate = bab_translate("Disabled");
			$this->t_delete_logs_before = bab_translate("Delete logs before (dd/mm/yyyy)");
			$this->t_save = bab_translate("Save");
		}
	}
	
	$connectionLoggingSetupTemplate = new ConnectionLoggingSetupTemplate();
	$babBody->babecho(bab_printTemplate($connectionLoggingSetupTemplate, 'admstats.html', 'edit_connection_logging_setup'));
}

/**
 * @param bool activate	Whether the logging for user connections should be activated.
 * @param BAB_DateTime deleteBefore	The date before which the logged connections must be removed or null if nothing should be removed.
 * 
 */
function saveConnectionLogSettings($activate, $deleteBefore = null)
{
	$registry = bab_getRegistryInstance();
	$registry->changeDirectory('/bab/statistics');
	$registry->setKeyValue('logConnections', $activate);
	
	if (!is_null($deleteBefore)) {
		//echo "bab_deleteConnectionLog(" . $deleteBefore->getIsoDate() . ")";
		bab_deleteConnectionLog($deleteBefore->getIsoDate());
	}
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

	case 'connections':
		$babBody->title = bab_translate("Connections Log");
		$babBody->addItemMenu("man", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admstats&idx=man");
		$babBody->addItemMenu("empty", bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=admstats&idx=empty");
		$babBody->addItemMenu("connections", bab_translate("Connections"), $GLOBALS['babUrlScript']."?tg=admstats&idx=connections");
		editConnectionLogSettings();
		break;

	case 'save_connections':
		$babBody->title = bab_translate("Connections Log");
		$babBody->addItemMenu("man", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admstats&idx=man");
		$babBody->addItemMenu("empty", bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=admstats&idx=empty");
		$babBody->addItemMenu("connections", bab_translate("Connections"), $GLOBALS['babUrlScript']."?tg=admstats&idx=connections");
		$activate = (bab_rp('activate') == 'activated');
		$remove = bab_rp('remove', false);
		if ($remove) {
			$removeBefore = bab_rp('remove_before', null);
		} else {
			$removeBefore = null;
		}
		if (!is_null($removeBefore)) {
			require_once $babInstallPath . 'utilit/dateTime.php';
			$removeBefore = BAB_DateTime::fromDateStr($removeBefore);
		}
		saveConnectionLogSettings($activate, $removeBefore);
		editConnectionLogSettings();
		$idx = 'connections';
		break;

	case 'cleanstats':
		$removeBefore = bab_rp('remove_before', '');

		if (!empty($removeBefore)) {
			require_once $babInstallPath . 'utilit/dateTime.php';
			$removeBefore = BAB_DateTime::fromDateStr($removeBefore);
			confirmCleanStatTables($removeBefore);
			$babBody->msgerror = bab_translate("Done");
		}
		else
		{
			$babBody->msgerror = bab_translate("Nothing done");
		}
		$idx= 'empty';
		/* no break; */
	case 'empty':
		$babBody->title = bab_translate("Clean statistics logs");
		cleanStatsTables();
		$babBody->addItemMenu("man", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admstats&idx=man");
		$babBody->addItemMenu("empty", bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=admstats&idx=empty");
		$babBody->addItemMenu("connections", bab_translate("Connections"), $GLOBALS['babUrlScript']."?tg=admstats&idx=connections");
		break;

	default:
	case 'man':
		$babBody->title = bab_translate("Groups List");
		$macl = new macl("admstats", "groups", 1, "aclman");
        $macl->addtable( BAB_STATSMAN_GROUPS_TBL,bab_translate("Who can manage statistics?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("man", bab_translate("Managers"), $GLOBALS['babUrlScript']."?tg=admstats&idx=man");
		$babBody->addItemMenu("empty", bab_translate("Empty"), $GLOBALS['babUrlScript']."?tg=admstats&idx=empty");
		$babBody->addItemMenu("connections", bab_translate("Connections"), $GLOBALS['babUrlScript']."?tg=admstats&idx=connections");
		$idx = 'man';
		break;
}

$babBody->setCurrentItemMenu($idx);

?>