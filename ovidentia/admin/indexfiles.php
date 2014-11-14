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
include_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';


/**
 * List index files for administrators
 */
function listIndexFiles()
	{
	global $babBody;

	class bab_listIndexFilesCls {

		var $db;
		var $reg;
		var $altbg = true;

		function bab_listIndexFilesCls() {
			$this->t_title			= bab_translate("Name");
			$this->t_all			= bab_translate("Index all files");
			$this->t_waiting		= bab_translate("Index waiting files");
			$this->t_onload			= bab_translate("Index on load");
			$this->t_disabled		= bab_translate("Disabled");
			$this->t_update			= bab_translate("Update");
			$this->t_allowed_ip		= bab_translate("Allowed IP address");
			$this->t_lock			= bab_translate("Lock");
			$this->t_unlock			= bab_translate("Unlock");
			$this->t_unlock_confirm = bab_toHtml(bab_translate("The index is probably in process, do you really want to unlock?"), BAB_HTML_JS);
			$this->t_all_all		= bab_translate("Index all files from all indexes");
			$this->t_all_waiting	= bab_translate("Index waiting files from all indexes");
			$this->t_confirm		= bab_toHtml(bab_translate("Indexing directly from interface could take a lot of cpu time, do you really want to index?"),BAB_HTML_JS);
			$this->t_checkall		= bab_translate("Check all");
			$this->t_uncheckall		= bab_translate("Uncheck all");

			global $babDB;

			$this->all = BAB_INDEX_ALL;
			$this->waiting = BAB_INDEX_WAITING;

			$this->reg = bab_getRegistryInstance();
			$this->reg->changeDirectory('/bab/indexfiles/');

			if (isset($_POST['action']) && 'index' == $_POST['action']) {
				$babDB->db_query("UPDATE ".BAB_INDEX_FILES_TBL." SET index_onload='0', index_disabled='0'");

				if (isset($_POST['onload'])) {
					foreach($_POST['onload'] as $id) {
						$babDB->db_query("UPDATE ".BAB_INDEX_FILES_TBL." SET index_onload='1' WHERE id=".$babDB->quote($id));
					}
				}

				if (isset($_POST['disabled'])) {
					foreach($_POST['disabled'] as $id) {
						$babDB->db_query("UPDATE ".BAB_INDEX_FILES_TBL." SET index_disabled='1' WHERE id=".$babDB->quote($id));
					}
				}

				$this->reg->setKeyValue('allowed_ip', $_POST['allowed_ip']);
			}

			$this->res = $babDB->db_query("
				SELECT f.*, s.object spool FROM 
					".BAB_INDEX_FILES_TBL." f 
					LEFT JOIN ".BAB_INDEX_SPOOLER_TBL." s ON s.object=f.object 
			");			
			
			$this->allowed_ip = $this->reg->getValue('allowed_ip', '127.0.0.1');
			
			if (isset($_GET['unlock']) && isset($_GET['obj'])) {
				$babDB->db_query('DELETE FROM '.BAB_INDEX_SPOOLER_TBL.' WHERE object='.$babDB->quote($_GET['obj']));
				header('location:'.$GLOBALS['babUrlScript']."?tg=admindex&idx=files");
				exit;
			}
		}


		function getnext() {
			global $babDB;
			if ($arr = $babDB->db_fetch_assoc($this->res)) {
				$this->altbg		= !$this->altbg;
				$this->id_index		= $arr['id'];
				$this->title		= bab_toHtml(bab_translate($arr['name']));
				$this->onload		= 1 == $arr['index_onload'];
				$this->disabled		= 1 == $arr['index_disabled'];
				$this->object		= bab_toHtml(urlencode($arr['object']));
				$this->locked		= NULL !== $arr['spool'];
				return true;
			}
			return false;
		}

	}	

	$temp = new bab_listIndexFilesCls();
	$babBody->babecho(	bab_printTemplate($temp, "indexfiles.html", "list"));
}


function status() {

	global $babBody;

	class temp {

		var $db;
		function temp() {

			$this->t_record = bab_translate("Record");

			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("SELECT * FROM ".BAB_INDEX_FILES_TBL." WHERE  
				object  IN('bab_files','bab_art_files','bab_forumsfiles', 'bab_articles')");
			$this->indexstatus = array(
					BAB_INDEX_STATUS_NOINDEX => bab_getIndexStatusLabel(BAB_INDEX_STATUS_NOINDEX),
					BAB_INDEX_STATUS_INDEXED => bab_getIndexStatusLabel(BAB_INDEX_STATUS_INDEXED),
					BAB_INDEX_STATUS_TOINDEX => bab_getIndexStatusLabel(BAB_INDEX_STATUS_TOINDEX)
				);
		}

		function getnextindex() {
			
			if ($arr = $this->db->db_fetch_assoc($this->res)) {
				$this->object		= bab_toHtml($arr['object']);
				$this->title		= bab_toHtml(bab_translate($arr['name']));
				
				return true;
			}
			return false;
		}

		function getnextstatus() {
			if (list($key, $val) = each($this->indexstatus)) {
				$this->value = bab_toHtml($key);
				$this->option = bab_toHtml($val);
				return true;
			}
			reset($this->indexstatus);
			return false;
		}
	}	

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "indexfiles.html", "status"));

}


function record_status() {
	$db = $GLOBALS['babDB'];
	foreach($_POST['status'] as $object => $status) {
		if ('' !== $status) {
			switch($object) {
				case 'bab_files':
					$db->db_query("UPDATE ".BAB_FILES_TBL." SET index_status='".$db->db_escape_string($status)."'");
					$db->db_query("UPDATE ".BAB_FM_FILESVER_TBL." SET index_status='".$db->db_escape_string($status)."'");
					break;

				case 'bab_art_files':
					$db->db_query("UPDATE ".BAB_ART_FILES_TBL." SET index_status='".$db->db_escape_string($status)."'");
					break;

				case 'bab_forumsfiles':
					$db->db_query("UPDATE ".BAB_FORUMSFILES_TBL." SET index_status='".$db->db_escape_string($status)."'");
					break;

				case 'bab_articles':
					$db->db_query("UPDATE ".BAB_ARTICLES_TBL." SET index_status='".$db->db_escape_string($status)."'");
					break;
			}
		}
	}

	return true;
}


// main

if( !isset($BAB_SESS_LOGGED) || empty($BAB_SESS_LOGGED) ||  !bab_isUserAdministrator() || false === bab_searchEngineInfos())
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}


$idx = bab_rp('idx','files');


if (isset($_POST['action'])) {
	switch($_POST['action']) {
		case 'status':
			if (!record_status()) {
				$idx = 'status';
			}
			break;
	}
}




$babBody->addItemMenu("files", bab_translate("Indexation"), $GLOBALS['babUrlScript']."?tg=admindex&idx=files");
$babBody->addItemMenu("status", bab_translate("files status"), $GLOBALS['babUrlScript']."?tg=admindex&idx=status");


switch($idx) {
	case 'files':
		$babBody->title = bab_translate("Search indexes");
		listIndexFiles();
		break;

	case 'status':
		$babBody->title = bab_translate("Change the status for all the files");
		status();
		break;
}

$babBody->setCurrentItemMenu($idx);


?>