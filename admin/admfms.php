<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
//include $babInstallPath."utilit/fmincl.php";

function addFolder()
	{
	global $babBody;
	class temp
		{
		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->moderator = bab_translate("Manager");
			$this->moderation = bab_translate("Approbation schema");
			$this->notification = bab_translate("Notifification");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->sabrowurl = $GLOBALS['babUrlScript']."?tg=lsa&idx=brow&cb=";
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			$this->active = bab_translate("Active");
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
		function temp()
			{
			global $babDB;
			$this->fullname = bab_translate("Folders");
			$this->manager = bab_translate("Manager");
			$this->notify = bab_translate("Notify");
			$this->active = bab_translate("Enabled");
			$this->notify = bab_translate("Notify");
			$this->modify = bab_translate("Update");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->res = $babDB->db_query("select * from ".BAB_FM_FOLDERS_TBL." order by folder asc");
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

				$this->fid = $arr['id'];
				$this->url = $GLOBALS['babUrlScript']."?tg=admfm&idx=modify&fid=".$arr['id'];
				$this->urlname = $arr['folder'];
				$this->managername = bab_getUserName($arr['manager']);
				$this->urluplo = $GLOBALS['babUrlScript']."?tg=admfm&idx=uplo&fid=".$arr['id'];
				$this->urldown = $GLOBALS['babUrlScript']."?tg=admfm&idx=down&fid=".$arr['id'];
				$this->urlupda = $GLOBALS['babUrlScript']."?tg=admfm&idx=upda&fid=".$arr['id'];
				$this->urluploname = bab_translate("Upload");
				$this->urldownname = bab_translate("Download");
				$this->urlupdaname = bab_translate("Write");
				$this->access = bab_translate("Acces");

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

function saveFolder($fname, $managerid, $active, $said, $notification)
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
		$babDB->db_query("insert into ".BAB_FM_FOLDERS_TBL." (folder, manager, idsa, filenotify, active) VALUES ('" .$fname. "', '" . $managerid. "', '". $said. "', '" . $notification. "', '" . $active. "')");
		}

}

function updateFolders($notifies, $actives)
{
	global $babDB;
	$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL."");
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

		$babDB->db_query("update ".BAB_FM_FOLDERS_TBL." set filenotify='".$not."', active='".$act."' where id='".$row['id']."'");
		}
}


/* main */
if( isset($add) && $add == "addfolder")
	saveFolder($fname, $managerid, $active, $said, $notification);

if( isset($update) && $update == "folders")
	updateFolders($notifies, $actives);

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