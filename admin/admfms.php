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

function addFolder()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $moderator;
		var $moderation;
		var $notification;
		var $usersbrowurl;
		var $yes;
		var $no;
		var $add;
		var $active;
		var $none;
		var $sares;
		var $sacount;
		var $saname;
		var $said;
		var $version;

		function temp()
			{
			global $babBody, $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->moderator = bab_translate("Manager");
			$this->moderation = bab_translate("Approbation schema");
			$this->notification = bab_translate("Notification");
			$this->version = bab_translate("Versioning");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			$this->active = bab_translate("Active");
			$this->none = bab_translate("None");
			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc");
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $babDB->db_num_rows($this->sares);
			}

		function getnextschapp()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp,"admfms.html", "foldercreate"));
	}

function listFolders()
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $manager;
		var $notify;
		var $access;
		var $modify;
		var $uncheckall;
		var $checkall;
		var $res;
		var $count;
		var $fnotify;
		var $factive;
		var $fversion;
		var $fid;
		var $url;
		var $urlname;
		var $managername;
		var $urluplo;
		var $urldown;
		var $urlupda;
		var $urluploname;
		var $urldownname;
		var $urlupdaname;
		var $access;
		var $version;

		function temp()
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Folders");
			$this->manager = bab_translate("Manager");
			$this->notify = bab_translate("Notify");
			$this->version = bab_translate("Versioning");
			$this->access = bab_translate("Access");
			$this->active = bab_translate("Enabled");
			$this->notify = bab_translate("Notify");
			$this->modify = bab_translate("Update");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->res = $babDB->db_query("select * from ".BAB_FM_FOLDERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by folder asc");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				if( $arr['filenotify'] == "Y")
					$this->fnotify = "checked";
				else
					$this->fnotify = "";

				if( $arr['active'] == "Y")
					$this->factive = "checked";
				else
					$this->factive = "";

				if( $arr['version'] == "Y")
					$this->fversion = "checked";
				else
					$this->fversion = "";

				$this->fid = $arr['id'];
				$this->url = $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$arr['id'];
				$this->urlname = $arr['folder'];
				$this->managername = bab_getUserName($arr['manager']);
				$this->urluplo = $GLOBALS['babUrlScript']."?tg=admfm&idx=uplo&fid=".$arr['id'];
				$this->urldown = $GLOBALS['babUrlScript']."?tg=admfm&idx=down&fid=".$arr['id'];
				$this->urlupda = $GLOBALS['babUrlScript']."?tg=admfm&idx=upda&fid=".$arr['id'];
				$this->urluploname = bab_translate("Upload");
				$this->urldownname = bab_translate("Download");
				$this->urlupdaname = bab_translate("Update");
				$this->access = bab_translate("Access");

				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admfms.html", "folderlist"));
	return $temp->count;
	}

function saveFolder($fname, $managerid, $active, $said, $notification, $version)
{
	global $babBody, $babDB;
	if( empty($fname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$fname = addslashes($fname);
		}

	$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where folder='".$fname."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This folder already exists");
		}
	else
		{
		if( empty($managerid))
			$managerid = 0;
		if( empty($said))
			$said = 0;
		$babDB->db_query("insert into ".BAB_FM_FOLDERS_TBL." (folder, manager, idsa, filenotify, active, version, id_dgowner) VALUES ('" .$fname. "', '" . $managerid. "', '". $said. "', '" . $notification. "', '" . $active. "', '" . $version. "', '" . $babBody->currentAdmGroup. "')");
		}

}

function updateFolders($notifies, $actives, $versions)
{
	global $babBody, $babDB;
	$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."'");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( count($notifies) > 0 && in_array($row['id'], $notifies))
			$not = "Y";
		else
			$not = "N";

		if( count($actives) > 0 && in_array($row['id'], $actives))
			$act = "Y";
		else
			$act = "N";

		if( count($versions) > 0 && in_array($row['id'], $versions))
			$ver = "Y";
		else
			$ver = "N";

		$babDB->db_query("update ".BAB_FM_FOLDERS_TBL." set filenotify='".$not."', active='".$act."', version='".$ver."' where id='".$row['id']."'");
		}
}


/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['filemanager'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "list";

if( isset($add) && $add == "addfolder")
	saveFolder($fname, $managerid, $active, $said, $notification, $version);

if( isset($update) && $update == "folders")
	updateFolders($notifies, $actives, $versions);

switch($idx)
	{
	case "addf":
		$babBody->title = bab_translate("Add a new folder");
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		addFolder();
		break;

	default:
	case "list":
		$babBody->title = bab_translate("File manager");
		if( listFolders() > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
			}

		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
