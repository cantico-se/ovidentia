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
include_once $babInstallPath."utilit/grpincl.php";
include_once $babInstallPath."utilit/fileincl.php";

function groupModify($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid group !!");
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $managertext;
		var $managerval;
		var $useemail;
		var $no;
		var $yes;
		var $noselected;
		var $yesselected;
		var $add;
		var $delete;
		var $bdel;

		var $usersbrowurl;
		var $grpid;

		var $grpdgtxt;
		var $grpdgid;
		var $grpdgname;
		var $count;
		var $res;
		var $selected;
		var $arr;
		var $bdggroup;

		function temp($id)
			{
			global $babBody, $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->managertext = bab_translate("Manager");
			$this->useemail = bab_translate("Use email");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Modify Group");
			$this->delete = bab_translate("Delete");
			$this->grpdgtxt = bab_translate("Delegation");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$req = "select * from ".BAB_GROUPS_TBL." where id='".$id."'";
			$res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($res);
			$this->grpid = $id;
			$this->grpname = $this->arr['name'];
			$this->grpdesc = $this->arr['description'];
			$this->identity = $this->arr['id_ocentity'];
			if( $this->arr['mail'] == "Y")
				{
				$this->noselected = "";
				$this->yesselected = "selected";
				}
			else
				{
				$this->noselected = "selected";
				$this->yesselected = "";
				}
			$req = "select * from ".BAB_USERS_TBL." where id='".$this->arr['manager']."'";
			$res = $babDB->db_query($req);
			if( $babDB->db_num_rows($res) > 0)
				{
				$arr = $babDB->db_fetch_array($res);
				$this->managerval = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$this->managerid = $arr['id'];
				}
			else
				{
				$this->managerid = "";
				$this->managerval = "";
				}
			if( $id > 3 )
				{
				$this->bdel = true;
				}
			else
				{
				$this->bdel = false;
				}
			$this->tgval = "group";
			$this->selected = "";
			$this->bdggroup = false;
			if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
				{
				$this->res = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL."");
				$this->count = $babDB->db_num_rows($this->res);
				if( $this->count > 0 )
					{
					$this->bdggroup = true;
					}
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;	
			if( $i < $this->count)
				{
				$rr = $babDB->db_fetch_array($this->res);
				$this->grpdgname = $rr['name'];
				$this->grpdgid = $rr['id'];
				if( $this->arr['id_dggroup'] == $this->grpdgid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			return false;
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"groups.html", "groupscreate"));
	}

function groupMembers($id)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $url;
		var $urlname;
		var $idgroup;
		var $group;
		var $grpid;
		var $primary;
		var $deletealt;
			
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $bmodname;
		var $bdel;
		var $altbg = true;

		function temp($id)
			{
			global $babBody;

			$this->grpid = $id;
			$this->t_lastname = bab_translate("Lastname");
			$this->t_firstname = bab_translate("Firstname");
			$this->t_upgrade = bab_translate("Upgrade");
			$this->t_delete = bab_translate("Delete");
			$this->deletealt = bab_translate("Delete group's members");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->idgroup = $id;
			
			$this->db = &$GLOBALS['babDB'];

			$req = "select ut.*, ugt.isprimary from ".BAB_USERS_TBL." ut left join ".BAB_USERS_GROUPS_TBL." ugt on ut.id=ugt.id_object where ugt.id_group= '".$id."' order by ut.lastname asc";
			
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $babBody->currentAdmGroup == 0)
				{
				$this->bmodname = true;
				}
			else
				{
				$this->bmodname = false;
				}

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$this->arr['id'];
				$this->urlname = bab_composeUserName($this->arr['firstname'], $this->arr['lastname']);
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "groups.html", "memberslist"));
	}



function deleteMembers($users, $item)
	{
	global $babBody, $idx;

	class tempa
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function tempa($users, $item)
			{
			global $BAB_SESS_USERID;
			$this->message = bab_translate("Are you sure you want to delete those members");
			$this->title = "";
			$names = "";
			$db = $GLOBALS['babDB'];
			for($i = 0; $i < count($users); $i++)
				{
				$req = "select * from ".BAB_USERS_TBL." where id='".$users[$i]."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". bab_composeUserName($arr['firstname'], $arr['lastname']);
					$names .= $arr['id'];
					}
				if( $i < count($users) -1)
					$names .= ",";
				}
			$this->warning = bab_translate("WARNING: This operation will delete members and their references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=group&idx=Deletem&item=".$item."&action=Yes&names=".$names;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=groups";
			$this->no = bab_translate("No");
			}
		}

	if( count($item) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		groupMembers($pos);
		$idx = "Members";
		return;
		}
	$tempa = new tempa($users, $item);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	}

function modifyGroup($name, $description, $managerid, $grpid, $grpdg)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_GROUPS_TBL." where id='".$grpid."'"));
	$res = $db->db_query("select * from ".BAB_GROUPS_TBL." where id!='".$grpid."' and name='".$name."'");
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("Group with the same name already exists!");
		return false;
		}
	else
		{
		if( empty($managerid))
			$managerid = 0;

		if( empty($grpdg))
			$grpdg = 0;

		$query = "update ".BAB_GROUPS_TBL." set name='".$name."', description='".$description."', manager='".$managerid."', id_dggroup='".$grpdg."' where id='".$grpid."'";
		$db->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=List");
	return true;
	}


function confirmDeleteMembers($item, $names)
{
	if( !empty($names))
	{
		$arr = explode(",", $names);
		$cnt = count($arr);
		$db = $GLOBALS['babDB'];
		for($i = 0; $i < $cnt; $i++)
			{
			bab_removeUserFromGroup($arr[$i], $item);
			}
	}
}

function confirmDeleteGroup($id)
	{
	if( $id <= 3)
		return;

	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteGroup($id);
	}


/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['groups'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "Modify";

if( isset($add))
	{
	if( isset($submit))
		{
		if( !isset($grpdg)) { $grpdg = 0; }
		if(!modifyGroup($name, $description, $managerid, $grpid, $grpdg))
			{
			$idx = "Modify";
			$item = $grpid;
		}
		else
			{
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			exit;
			}
		}
	}

if( isset($action) && $action == "Yes")
	{
	if($idx == "Delete")
		{
		confirmDeleteGroup($group);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		exit;
		}
	if($idx == "Deletem")
		{
		confirmDeleteMembers($item, $names);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		exit;
		}
	}


switch($idx)
	{
	case "Deletem":
		if( isset($users) && count($users) > 0)
			{
			deleteMembers($users, $item);
			$babBody->title = bab_translate("Delete group's members");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
			$babBody->addItemMenu("Deletem", bab_translate("Delete"), "");
			break;
			}
		/* no break */
	case "Members":
		groupMembers($item);
		$babBody->title = bab_translate("Group's members").' : '.bab_getGroupName($item);
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		if( $babBody->currentAdmGroup > 0 && $babBody->currentDGGroup['id_group'] != $item )
			{
			$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$item);
			}
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		$babBody->addItemMenu("Add", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=users&idx=List&grp=".$item);
		break;
	case "deldg":
		if( $item > 3 )
			groupAdmDelete($item);
		$babBody->title = bab_translate("Delete group");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$item);
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		$babBody->addItemMenu("deldg", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=group&idx=deldg&item=".$item);
		break;
	case "Modify":
	default:
		groupModify($item);
		$babBody->title = bab_getGroupName($item) . " ". bab_translate("group");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$item);
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>
