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

$babDG = array(	array("groups", bab_translate("Groups")),
				array("sections", bab_translate("Sections")),
				array("articles", bab_translate("Topics categories")),
				array("faqs", bab_translate("Faq")),
				array("forums", bab_translate("Forums")),
				array("calendars", bab_translate("Calendar")),
				array("mails", bab_translate("Mail")),
				array("directories", bab_translate("Directories")),
				array("approbations", bab_translate("Approbations")),
				array("filemanager", bab_translate("File manager"))
				);

function delgatList($res)
	{
	global $babBody;
	class temp
		{

		var $delegtxt;
		var $delegdesctxt;
		var $url;
		var $urltxt;
		var $delegval;
		var $res;
		var $count;
		var $memberstxt;
		var $urlmem;

		function temp($res)
			{
			global $babDB;
			$this->delegtxt = bab_translate("Groupes");
			$this->delegdesctxt = bab_translate("Description");
			$this->memberstxt = bab_translate("Members");
			$this->res = $res;
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->delegval = $arr['description'];
				$this->urltxt = $arr['name'];
				$this->url = $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$arr['id'];
				$this->urlmem = $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($res);
	$babBody->babecho(	bab_printTemplate($temp, "delegat.html", "delegationlist"));
	}

function groupDelegatMembers($id)
	{
	global $babBody;
	class temp
		{

		var $fullname;
		var $fullnameval;
		var $usersbrowurl;
		var $userid;
		var $userstxt;
		var $delusers;

		function temp($id)
			{
			global $babDB;
			$this->userstxt = bab_translate("Add");
			$this->fullname = bab_translate("Fullname");
			$this->res = $babDB->db_query("select * from ".BAB_DG_USERS_GROUPS_TBL." where id_group=".$id);
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=aclug&idx=list&table=".BAB_DG_USERS_GROUPS_TBL."&return=mem&target=delegat&idgroup=".$id;
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fullnameval = bab_getUserName($arr['id_object']);
				$this->userid = $arr['id_object'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "delegat.html", "delegatmembers"));
	}


function groupDelegatCreate($gname, $description)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $add;
		var $id;
		var $tgval;
		var $what;
		var $bdel;

		function temp($gname, $description)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->add = bab_translate("Add Group");
			$this->id = "";
			if( bab_isMagicQuotesGpcOn())
				{
				$this->grpdesc = htmlentities(stripslashes($description));
				$this->grpname = htmlentities(stripslashes($gname));
				}
			else
				{
				$this->grpname = htmlentities($gname);
				$this->grpdesc = htmlentities($description);
				}
			$this->bdel = false;
			$this->tgval = "delegat";
			$this->what = "add";
			$this->checked = '';
			}

		function getnext()
			{
			global $babDB, $babDG;
			static $i = 0;
			if( $i < count($babDG))
				{
				$this->delegitem = $babDG[$i][0];
				$this->delegitemdesc = $babDG[$i][1];
				$i++;
				return true;
				}
			else
				return false;

			}

		}

	$temp = new temp($gname, $description);
	$babBody->babecho(	bab_printTemplate($temp,"delegat.html", "delegatcreate"));
	}

function groupDelegatModify($gname, $description, $id)
	{
	global $babBody;

	class temp
		{
		var $name;
		var $description;
		var $add;
		var $delete;
		var $bdel;
		var $what;
		var $arr = array();
		var $delegitem;
		var $delegitemdesc;
		var $checked;

		var $id;

		function temp($gname, $description, $id)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->add = bab_translate("Modify Group");
			$this->delete = bab_translate("Delete");
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".BAB_DG_GROUPS_TBL." where id='".$id."'");
			$this->arr = $db->db_fetch_array($res);
			$this->id = $id;


			list($total) = $db->db_fetch_row($db->db_query("select count(id) as total from ".BAB_GROUPS_TBL." where id_dggroup='".$id."'"));
			if( $total > 0 )
				$this->bdel = false;
			else
				$this->bdel = true;

			if( bab_isMagicQuotesGpcOn())
				{
				$gname = stripslashes($gname);
				$description = stripslashes($description);
				}
			
			if( $gname != '' )
				$this->grpname = htmlentities($gname);
			else
				$this->grpname = htmlentities($this->arr['name']);

			if( $gname != '' )
				$this->grpdesc = htmlentities($description);
			else
				$this->grpdesc = htmlentities($this->arr['description']);
			$this->tgval = "delegat";
			$this->what = "mod";
			}

		function getnext()
			{
			global $babDB, $babDG;
			static $i = 0;
			if( $i < count($babDG))
				{
				$this->delegitem = $babDG[$i][0];
				$this->delegitemdesc = $babDG[$i][1];
				if( $this->arr[$babDG[$i][0]] == 'Y')
					$this->checked = 'checked';
				else
					$this->checked = '';
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($gname, $description, $id);
	$babBody->babecho(	bab_printTemplate($temp,"delegat.html", "delegatcreate"));
	}


function deleteDelegatGroup($id)
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

		function temp($id)
			{
			global $babDB;
			$this->message = bab_translate("Are you sure you want to delete this group");
			list($this->title) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_DG_GROUPS_TBL." where id='".$id."'"));
			$this->warning = bab_translate("WARNING: This operation will delete group and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=delegat&idx=list&id=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function addDelegatGroup($name, $description, $delegitems)
	{
	global $babBody, $babDB;

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

	$res = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL." where name='".$name."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This group already exists");
		return false;
		}
	else
		{
		$req1 = "(name, description";
		$req2 = "('" .$name. "', '" . $description. "'";
		for( $i = 0; $i < count($delegitems); $i++)
			{
			$req1 .= ", ". $delegitems[$i];
			$req2 .= ", 'Y'";
			}
		
		$req1 .= ")";
		$req2 .= ")";
		$babDB->db_query("insert into ".BAB_DG_GROUPS_TBL." ".$req1." VALUES ".$req2);
		$id = $babDB->db_insert_id();
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
	exit;
	}

function modifyDelegatGroup($name, $description, $delegitems, $id)
	{
	global $babBody, $babDB, $babDG;

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

	$res = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL." where id!='".$id."' and name='".$name."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("Group with the same name already exists!");
		return false;
		}
	else
		{
		$req = "update ".BAB_DG_GROUPS_TBL." set name='".$name."', description='".$description."'";
		$cnt = count($delegitems);
		for( $i = 0; $i < count($babDG); $i++)
			{
			if( $cnt > 0 && in_array($babDG[$i][0], $delegitems))
				$req .= ", ". $babDG[$i][0]."='Y'";
			else
				$req .= ", ". $babDG[$i][0]."='N'";
			}

		$babDB->db_query($req ." where id='".$id."'");
		}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
	exit;
	}

function updateDelegatMembers( $grp, $users, $userst)
{
	global $babDB;

	if( !empty($userst))
		$tab = explode(",", $userst);
	else
		$tab = array();

	for( $i = 0; $i < count($tab); $i++)
	{
		if( count($users) < 1 || !in_array($tab[$i], $users))
		{
			$babDB->db_query("delete from ".BAB_DG_USERS_GROUPS_TBL." where id_group='".$grp."' and id_object='".$tab[$i]."'");
		}
	}
	for( $i = 0; $i < count($users); $i++)
	{
		if( count($tab) < 1 || !in_array($users[$i], $tab))
		{
			$babDB->db_query("insert into ".BAB_DG_USERS_GROUPS_TBL." (id_group, id_object) VALUES ('" .$grp. "', '" . $users[$i]. "')");
		}
	}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=aclug&idx=unload&url=".urlencode($GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$grp));
	exit;
	
}

function deleteDelegatMembers( $grp, $users)
{
	global $babDB;

	for( $i = 0; $i < count($users); $i++)
	{
		$babDB->db_query("delete from ".BAB_DG_USERS_GROUPS_TBL." where id_group='".$grp."' and id_object='".$users[$i]."'");
	}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$grp);
	exit;
}

function confirmDeleteDelegatGroup( $id)
{
	global $babDB;
	list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_GROUPS_TBL." where id_dggroup='".$id."'"));
	if( $total > 0 )
		return;
	$babDB->db_query("delete from ".BAB_DG_USERS_GROUPS_TBL." where id_group='".$id."'");
	$babDB->db_query("delete from ".BAB_DG_GROUPS_TBL." where id='".$id."'");
	$babDB->db_query("update ".BAB_GROUPS_TBL." set id_dggroup='0' where id_dggroup='".$id."'");
}

/* main */
if( !$babBody->isSuperAdmin )
	{
	$babBody->title = bab_translate("Access denied");
	exit;
	}
	
if( !isset($idx))
	$idx = "list";

if( isset($add))
	{
	if( isset($submit))
		{
		if( $add == 'add')
			{
			if( !addDelegatGroup($gname, $description, $delegitems ))
				{
				$idx = 'new';
				}
			else
				$idx = 'list';
			}
		else if( $add == 'mod' )
			{
			if(!modifyDelegatGroup($gname, $description, $delegitems, $id))
				$idx = "mod";
			else
				$idx = 'list';
			}

		}
	else if( isset($deleteg) )
		{
		$idx = "gdel";
		}
	}
else if( isset($updateg) )
	{
	updateDelegatMembers($idgroup, $users, $userst);
	}
else if( isset($memdel) )
	{
	deleteDelegatMembers($id, $users);
	}
else if( isset($action) && $action == "Yes")
	{
	confirmDeleteDelegatGroup($id);
	$idx = 'list';
	}

if( $idx == 'list' )
{
	$dgres = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL."");
	if( !$dgres || $babDB->db_num_rows($dgres) == 0 )
		$idx = 'new';
}


switch($idx)
	{
	case "gdel":
		deleteDelegatGroup($id);
		$babBody->title = bab_translate("Delete delegation group");
		$babBody->addItemMenu("list", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("gdel", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=delegat&idx=gdel&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "mem":
		groupDelegatMembers($id);
		$babBody->title = bab_translate("Members of delegation group");
		$babBody->addItemMenu("list", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "mod":
		if( !isset($gname))	$gname = '';
		if( !isset($description)) $description = '';
		groupDelegatModify($gname, $description, $id);
		$babBody->title = bab_translate("Modify delegation group");
		$babBody->addItemMenu("list", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "new":
		if( !isset($gname))	$gname = '';
		if( !isset($description)) $description = '';
		groupDelegatCreate($gname, $description);
		$babBody->title = bab_translate("Create delegation group");
		$babBody->addItemMenu("list", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;

	case "list":
	default:
		delgatList($dgres);
		$babBody->title = bab_translate("Groups of delegation list");
		$babBody->addItemMenu("list", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>