<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."admin/acl.php";
//include $babInstallPath."utilit/fmincl.php";

function bab_getFolderName($id)
	{
	global $babDB;
	$res = $babDB->db_query("select folder from ".BAB_FM_FOLDERS_TBL." where id='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['folder'];
		}
	else
		{
		return "";
		}
	}

function modifyFolder($fid)
	{
	global $babBody;
	class temp
		{
		function temp($fid)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->moderator = bab_translate("Manager");
			$this->moderation = bab_translate("Approbation schema");
			$this->notification = bab_translate("Notification");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->sabrowurl = $GLOBALS['babUrlScript']."?tg=lsa&idx=brow&cb=";
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
			$arr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_FLOW_APPROVERS_TBL." where id='".$arr['idsa']."'"));
			$this->saname = $arr['name'] == "" ? "": $arr['name'];
			}

		}

	$temp = new temp($fid);
	$babBody->babecho(bab_printTemplate($temp,"admfms.html", "foldermodify"));
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
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admfm&idx=list&fid=".$fid."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admfms&idx=list";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($fid);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function updateFolder($fid, $fname, $managerid, $active, $said, $notification)
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
		$babDB->db_query("update ".BAB_FM_FOLDERS_TBL." set folder='".$fname."', manager='".$managerid."', idsa='".$said."', filenotify='".$notification."', active='".$active."' where id ='".$fid."'");
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		exit;
		}
}

function confirmDeleteFolder($fid)
{
	global $babDB;
	// delete files owned by this group
	bab_deleteUploadUserFiles("Y", $fid);

    // delete group
	$babDB->db_query("delete from ".BAB_FM_FOLDERS_TBL." where id='".$fid."'");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
	exit;
}

/* main */

if( isset($mod) && $mod == "modfolder")
{
	if( isset($bupdate))
		updateFolder($fid, $fname, $managerid, $active, $said, $notification);
	else if(isset($bdel))
		$idx = "delf";
}


if( isset($action) && $action == "Yes")
	{
	confirmDeleteFolder($fid);
	}


if( isset($aclview))
	{
	aclUpdate($table, $item, $groups, $what);
	$fid = $item;
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
		$babBody->title = bab_translate("Delete folder");
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		deleteFolder($fid);
		break;

	default:
	case "modify":
		$babBody->title = bab_translate("Modify folder");
		modifyFolder($fid);
		$babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=admfms&idx=list");
		$babBody->addItemMenu("addf", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfms&idx=addf");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfm&idx=modify");
		$babBody->addItemMenu("down", bab_translate("Download"), $GLOBALS['babUrlScript']."?tg=admfm&idx=down&fid=".$fid);
		$babBody->addItemMenu("uplo", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=admfm&idx=uplo&fid=".$fid);
		$babBody->addItemMenu("upda", bab_translate("Write"), $GLOBALS['babUrlScript']."?tg=admfm&idx=upda&fid=".$fid);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>