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
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/fileincl.php";

function modifyFolder($fid)
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
		var $del;
		var $active;
		var $fid;
		var $folderval;
		var $said;
		var $manager;
		var $none;
		var $yactsel;
		var $nactsel;
		var $ynfsel;
		var $safm;
		var $sares;
		var $sacount;
		var $version;
		var $yversel;
		var $nversel;

		function temp($fid)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->moderator = bab_translate("Manager");
			$this->moderation = bab_translate("Approbation schema");
			$this->notification = bab_translate("Notification");
			$this->version = bab_translate("Versioning");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Update");
			$this->del = bab_translate("Delete");
			$this->active = bab_translate("Active");
			$this->fid = $fid;
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FM_FOLDERS_TBL." where id ='".$fid."'"));
			$this->folderval = $arr['folder'];
			$this->said = $arr['idsa'];
			$this->manager = bab_getUserName($arr['manager']);
			$this->managerid = $arr['manager'];
			$this->none = bab_translate("None");
			if( $arr['active'] == "Y" )
				{
				$this->yactsel = "selected";
				$this->nactsel = "";
				}
			else
				{
				$this->nactsel = "selected";
				$this->yactsel = "";
				}

			if( $arr['filenotify'] == "Y" )
				{
				$this->ynfsel = "selected";
				$this->nnfsel = "";
				}
			else
				{
				$this->nnfsel = "selected";
				$this->ynfsel = "";
				}

			if( $arr['version'] == "Y" )
				{
				$this->yversel = "selected";
				$this->nversel = "";
				}
			else
				{
				$this->nversel = "selected";
				$this->yversel = "";
				}

			$this->safm = $arr['idsa'];

			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
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
				if( $this->said == $this->safm )
					$this->sasel = "selected";
				else
					$this->sasel = "";
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new temp($fid);
	$babBody->babecho(bab_printTemplate($temp,"admfms.html", "foldermodify"));
	}


function fieldsFolder($fid)
	{
	global $babBody;
	
	class temp
		{
		var $fid;
		var $fieldname;
		var $fieldnameval;
		var $fieldid;
		var $altdelf;
		var $fieldurl;
		var $fielddefval;
		var $defaultname;

		function temp($fid)
			{
			global $babDB;
			$this->fid = $fid;
			$this->fieldname = bab_translate("Field");
			$this->defaultname = bab_translate("Default Value");
			$this->altdelf = bab_translate("Delete fields");
			$this->res = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$fid."'order by id asc");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fieldnameval = $arr['name'];
				$this->fielddefval = $arr['defaultval'];
				$this->fieldid = $arr['id'];
				$this->fieldurl = $GLOBALS['babUrlScript']."?tg=admfm&idx=mfield&fid=".$this->fid."&ffid=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($fid);
	$babBody->babecho(	bab_printTemplate($temp,"admfms.html", "fmfields"));
	}

function addFieldFolder($fid, $fname, $defval)
	{
	global $babBody;
	
	class tempa
		{
		var $fid;
		var $ffid;
		var $field;
		var $add;
		var $fname;
		var $what;
		var $defval;
		var $default;

		function tempa($fid, $fname, $defval)
			{
			$this->fid = $fid;
			$this->ffid = '';
			$this->field = bab_translate("Field name");
			$this->default = bab_translate("Default Value");
			$this->add = bab_translate("Add");
			if( !empty($fname))
				{
				$fname = htmlentities(stripslashes($fname));
				}
			if( !empty($defval))
				{
				$defval = htmlentities(stripslashes($defval));
				}
			$this->fname = $fname;
			$this->defval = $defval;
			$this->what = 'fadd';
			}

		}

	$temp = new tempa($fid, $fname, $defval);
	$babBody->babecho(	bab_printTemplate($temp,"admfms.html", "fmfieldadd"));
	}

function modifyFieldFolder($fid, $ffid, $fname, $defval)
	{
	global $babBody;
	
	class tempa
		{
		var $fid;
		var $ffid;
		var $field;
		var $add;
		var $fname;
		var $what;
		var $default;
		var $defval;

		function tempa($fid, $ffid, $fname, $defval)
			{
			global $babDB;
			$this->fid = $fid;
			$this->field = bab_translate("Field name");
			$this->default = bab_translate("Default value");
			$this->add = bab_translate("Modify");
			$this->fname = $fname;
			$this->defval = $defval;
			$this->what = 'fmod';
			if( empty($fname))
				{
				$res = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id='".$ffid."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->fname = htmlentities($arr['name']);
					$this->defval = htmlentities($arr['defaultval']);
					}
				}
			}

		}

	$temp = new tempa($fid, $ffid, $fname, $defval);
	$babBody->babecho(	bab_printTemplate($temp,"admfms.html", "fmfieldadd"));
	}

function deleteFolder($fid)
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
		var $topics;
		var $article;

		function temp($fid)
			{
			$this->message = bab_translate("Are you sure you want to delete this folder");
			$this->title = bab_getFolderName($fid);
			$this->warning = bab_translate("WARNING: This operation will delete the folder with all files"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admfm&idx=list&fid=".$fid."&action=fyes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admfms&idx=list";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($fid);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function deleteFieldsFolder($fid, $fields)
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
		var $topics;
		var $article;

		function temp($fid, $fields)
			{
			$this->message = bab_translate("Are you sure you want to delete selected fields");
			$this->title = bab_getFolderName($fid);
			$this->warning = bab_translate("WARNING: This operation will delete those fields with their values"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid."&action=ffyes&fields=".implode(',', $fields);
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($fid, $fields);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function updateFolder($fid, $fname, $managerid, $active, $said, $notification, $version)
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

	$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where folder='".$fname."' and id!='".$fid."'");
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

		list($idsafolder, $bnotify) = $babDB->db_fetch_row($babDB->db_query("select idsa, filenotify from ".BAB_FM_FOLDERS_TBL." where id='".$fid."'"));
		if( $idsafolder != $said )
			{
			include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id_owner='".$fid."' and bgroup='Y' and confirmed='N'");
			while( $row = $babDB->db_fetch_array($res))
				{
				if( $row['idfai'] != 0 )
					{
					deleteFlowInstance($row['idfai']);
					}
				if( $said == 0 )
					{
					$babDB->db_query("update ".BAB_FILES_TBL." set idfai='0', confirmed = 'Y' where id='".$row['id']."'");
					}
				else
					{
					$idfai = makeFlowInstance($said, "fil-".$row['id']);
					$babDB->db_query("update ".BAB_FILES_TBL." set idfai='".$idfai."' where id='".$row['id']."'");
					$nfusers = getWaitingApproversFlowInstance($idfai, true);
					if( count($nfusers) > 0 )
						notifyFileApprovers($row['id'], $nfusers, bab_translate("A new file is waiting for you"));
					}

				$res2 = $babDB->db_query("select * from ".BAB_FM_FILESVER_TBL." where id_file='".$row['id']."' and confirmed='N'");
				while( $rrr = $babDB->db_fetch_array($res2))
					{
					if( $rrr['idfai'] != 0 )
						deleteFlowInstance($rrr['idfai']);
					if( $said == 0 )
						{
						acceptFileVersion($row, $rrr, $bnotify);
						}
					else
						{
						$idfai = makeFlowInstance($said, "filv-".$rrr['id']);
						$babDB->db_query("update ".BAB_FM_FILESVER_TBL." set idfai='".$idfai."' where id='".$rrr['id']."'");
						$nfusers = getWaitingApproversFlowInstance($idfai, true);
						if( count($nfusers) > 0 )
							notifyFileApprovers($row['id'], $nfusers, bab_translate("A new version file is waiting for you"));
						}
					}


				}
			}

		
		$babDB->db_query("update ".BAB_FM_FOLDERS_TBL." set folder='".$fname."', manager='".$managerid."', idsa='".$said."', filenotify='".$notification."', active='".$active."', version='".$version."' where id ='".$fid."'");
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		exit;
		}
}

function confirmDeleteFolder($fid)
{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteFolder($fid);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	exit;
}

function confirmDeleteFields($fid, $fields)
{
	global $babDB;

	if( !empty($fields))
	{
	$arr = explode(',', $fields);
	for( $i = 0; $i < count($arr); $i++)
		{
		$babDB->db_query("delete from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$arr[$i]."'");
		$babDB->db_query("delete from ".BAB_FM_FIELDS_TBL." where id='".$arr[$i]."' and id_folder='".$fid."'");
		}
	}
}

function addField($fid, $ffname, $defval)
{
	global $babBody, $babDB;

	if( !bab_isMagicQuotesGpcOn())
		{
		$ffname = addslashes($ffname);
		$defval = addslashes($defval);
		}

	$res = $babDB->db_query("select id from ".BAB_FM_FIELDS_TBL." where name='".$ffname."' and id_folder='".$fid."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This field already exists");
		return false;
		}
	else
		{
		$babDB->db_query("insert into ".BAB_FM_FIELDS_TBL." (id_folder, name, defaultval) VALUES ('" .$fid. "', '" . $ffname. "', '" . $defval. "')");
		}

	return true;
}

function modifyField($fid, $ffid, $ffname, $defval)
{
	global $babBody, $babDB;

	if( !bab_isMagicQuotesGpcOn())
		{
		$ffname = addslashes($ffname);
		$defval = addslashes($defval);
		}

	$babDB->db_query("update ".BAB_FM_FIELDS_TBL." set name='" . $ffname. "', defaultval='".$defval."' where id='".$ffid."'");

	return true;
}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['filemanager'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( isset($mod) && $mod == "modfolder")
{
	if( isset($bupdate))
		updateFolder($fid, $fname, $managerid, $active, $said, $notification, $version);
	else if(isset($bdel))
		$idx = "delf";
}
else if( isset($action))
	{
	if( $action == 'fyes')
		confirmDeleteFolder($fid);
	else if( $action == 'ffyes' )
		confirmDeleteFields($fid, $fields);
	}
else if( isset($fmf))
	{
	if( $fmf == 'fadd' )
		{
		if( !addField($fid, $ffname, $defval))
			$idx = 'afield';
		}
	else if( $fmf == 'fmod' )
		{
		if( !modifyField($fid, $ffid, $ffname, $defval))
			$idx = 'mfield';
		}

	}
else if( isset($aclview))
	{
	aclUpdate($table, $item, $groups, $what);
	if( $table == BAB_FMDOWNLOAD_GROUPS_TBL )
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfm&idx=uplo&fid=".$item);
	else if( $table == BAB_FMUPLOAD_GROUPS_TBL )
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfm&idx=upda&fid=".$item);
	else 
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	exit;
	}

switch($idx)
	{
	case "uplo":
		$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("List of groups");
		aclGroups("admfm", "modify", BAB_FMUPLOAD_GROUPS_TBL, $fid, "aclview");
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
		$babBody->addItemMenu("down", bab_translate("Download"), $GLOBALS['babUrlScript']."?tg=admfm&idx=down&fid=".$fid);
		$babBody->addItemMenu("uplo", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=admfm&idx=uplo&fid=".$fid);
		$babBody->addItemMenu("upda", bab_translate("Write"), $GLOBALS['babUrlScript']."?tg=admfm&idx=upda&fid=".$fid);
		break;
	
	case "down":
		$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("List of groups");
		aclGroups("admfm", "modify", BAB_FMDOWNLOAD_GROUPS_TBL, $fid, "aclview");
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
		$babBody->addItemMenu("down", bab_translate("Download"), $GLOBALS['babUrlScript']."?tg=admfm&idx=down&fid=".$fid);
		$babBody->addItemMenu("uplo", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=admfm&idx=uplo&fid=".$fid);
		$babBody->addItemMenu("upda", bab_translate("Write"), $GLOBALS['babUrlScript']."?tg=admfm&idx=upda&fid=".$fid);
		break;

	case "upda":
		$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("List of groups");
		aclGroups("admfm", "modify", BAB_FMUPDATE_GROUPS_TBL, $fid, "aclview");
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
		$babBody->addItemMenu("down", bab_translate("Download"), $GLOBALS['babUrlScript']."?tg=admfm&idx=down&fid=".$fid);
		$babBody->addItemMenu("uplo", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=admfm&idx=uplo&fid=".$fid);
		$babBody->addItemMenu("upda", bab_translate("Write"), $GLOBALS['babUrlScript']."?tg=admfm&idx=upda&fid=".$fid);
		break;

	case "delf":
		$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("Delete folder");
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		deleteFolder($fid);
		break;

	case "mfield":
		$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("Modify folder's field");
		if( !isset($ffname)) $ffname = '';
		if( !isset($defval)) $defval = '';
		modifyFieldFolder($fid, $ffid, $ffname, $defval);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
		$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
		$babBody->addItemMenu("mfield", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=mfield&fid=".$fid);
		break;

	case "afield":
		$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("Add folder's field");
		if( !isset($ffname)) $ffname = '';
		if( !isset($defval)) $defval = '';
		addFieldFolder($fid, $ffname, $defval);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
		$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
		$babBody->addItemMenu("afield", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfm&idx=afield&fid=".$fid);
		break;

	case "delff":
		if( count($fields) > 0)
		{
			$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("Delete folder's fields");
			deleteFieldsFolder($fid, $fields);
			$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
			$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
			$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
			$babBody->addItemMenu("delff", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=admfm&idx=delff&fid=".$fid);
			break;
		}
		/* no break ; */
	case "fields":
		$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("Folder's fields");
		fieldsFolder($fid);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$fid);
		$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
		$babBody->addItemMenu("afield", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfm&idx=afield&fid=".$fid);
		break;

	default:
	case "modify":
		$babBody->title = bab_getFolderName($fid) . ": ".bab_translate("Modify folder");
		modifyFolder($fid);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify");
		$babBody->addItemMenu("fields", bab_translate("Fields"), $GLOBALS['babUrlScript']."?tg=admfm&idx=fields&fid=".$fid);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
