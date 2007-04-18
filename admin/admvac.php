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

/**
* @internal SEC1 PR 18/04/2007 FULL
*/

/*

include_once $babInstallPath."admin/acl.php";
include_once $babInstallPath."utilit/vacincl.php";

function modifyVacation($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("You must choose a valid vacation")." !!";
		return;
		}

	class temp
		{
		var $name;
		var $description;
		var $defaultdays;
		var $maxdays;
		var $maxdaysauthorized;
		var $update;

		var $arr = array();
		var $arr2 = array();
		var $res;
		var $delete;

		function temp($id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->defaultdays = bab_translate("Default days number");
			$this->maxdays = bab_translate("Max days number");
			$this->maxdaysauthorized = bab_translate("Max days authorized");
			$this->update = bab_translate("Update");
			$this->delete = bab_translate("Delete");

			global $babDB;
			$req = "select * from ".BAB_VACATIONS_TYPES_TBL." where id=".$babDB->quote($id);
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			}
		}

	$temp = new temp($id);
	$babBody->babecho(bab_printTemplate($temp,"admvacs.html", "vacationmodify"));
	}

function deleteVacation($id)
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

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this vacation");
			$this->title = bab_getVacationName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the vacation and all references"). "!";
			$this->urlyes = bab_toHtml($GLOBALS['babUrlScript']."?tg=admvac&idx=delete&vacation=".$id."&action=Yes");
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=admvac&idx=modify&item=".$id);
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function modifyStatus($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("You must choose a valid status")." !!";
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $modify;

		var $db;
		var $arr = array();
		var $res;

		function temp($id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->modify = bab_translate("Modify Status");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VACATIONS_STATES_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"admvacs.html", "statusmodify"));
	}

function deleteStatus($id)
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

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this status");
			$this->title = bab_getStatusName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the status and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admvac&idx=deletestatus&status=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admvac&idx=modifystatus&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function updateVacation($id, $name, $description, $defaultnday, $maxdays, $maxdaysauthorized)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return;
		}

	$dnd = sscanf($defaultnday, "%d");
	if( empty($dnd[0]))
		{
		$dndval = 0;
		}
	else
		$dndval = abs($dnd[0]);
	$maxd = sscanf($maxdays, "%d");
	if( empty($maxd[0]))
		{
		$maxdval = 0;
		}
	else
		$maxdval = abs($maxd[0]);

	$maxdauth = sscanf($maxdaysauthorized, "%d");
	if( empty($maxdauth[0]))
		{
		$maxdauth = $maxdval;
		}
	else
		{
		$maxdauth = abs($maxdauth[0]);
		}
	if( $maxdauth > $maxdval )
		$maxdauth = $maxdval;

	$db = $GLOBALS['babDB'];

	$query = "update ".BAB_VACATIONS_TYPES_TBL." set name='$name', description='$description', defaultdays='$dndval', maxdays='$maxdval', days='$maxdauth' where id = '$id'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");

	}

function confirmDeleteVacation($id)
	{
	
	$db = &$GLOBALS['babDB'];

	include_once $GLOBALS['babInstallPath']."admin/acl.php";
	aclDelete(BAB_VACATIONSVIEW_GROUPS_TBL, $id);
	
	$req = "delete from ".BAB_VACATIONS_TYPES_TBL." where id='$id'";
	$res = $db->db_query($req);
	}

function confirmDeleteStatus($id)
	{	
	$db = $GLOBALS['babDB'];

	$req = "delete from ".BAB_VACATIONS_STATES_TBL." where id='$id'";
	$res = $db->db_query($req);

	}

function updateStatus($id, $name, $description)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name")." !!";
		return;
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_VACATIONS_STATES_TBL." where id ='$id'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("The state doesn't exist");
		}
	else
		{
		$query = "update ".BAB_VACATIONS_STATES_TBL." set status='$name', description='$description' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admvacs&idx=liststatus");
	}

// main 
if(!isset($idx))
	{
	$idx = "modify";
	}

if( isset($update) && $update == "updatevacation")
	{
	if( isset($Submit))
		updateVacation($item, $name, $description, $defaultnday, $maxdays, $maxdaysauthorized);
	else if( isset($vacdel))
		$idx = "delete";
	}

if( isset($updatestatus) && $updatestatus == "update")
	{
	updateStatus($item, $name, $description);
	}

if( isset($action) && $action == "Yes")
	{
	if( $idx == "delete")
		{
		confirmDeleteVacation($vacation);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");
		}
	if( $idx == "deletestatus")
		{
		confirmDeleteStatus($status);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admvacs&idx=liststatus");
		}
	}

switch($idx)
	{

	case "groups":
		$babBody->title = bab_translate("List of groups");
		aclGroups("vacation", "modify", BAB_VACATIONSVIEW_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admvac&idx=modify&item=".$item);
		break;

	case "delete":
		$babBody->title = bab_translate("Delete vacation");
		deleteVacation($item);
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admvac&idx=modify&item=".$item);
		$babBody->addItemMenu("delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=admvac&idx=delete&item=".$item);
		break;

	case "modifystatus":
		$babBody->title = bab_translate("Modify status");
		modifyStatus($item);
		$babBody->addItemMenu("liststatus", bab_translate("Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=liststatus");
		$babBody->addItemMenu("modifystatus", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admvac&idx=modifystatus&item=".$item);
		break;

	default:
	case "modify":
		$babBody->title = bab_translate("Modify vacation");
		modifyVacation($item);
		$babBody->addItemMenu("list", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admvac&idx=modify&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);
*/
?>