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
				array("filemanager", bab_translate("File manager")),
				array("orgchart", bab_translate("Charts"))
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
		var $altbg = true;

		function temp($res)
			{
			global $babDB;
			$this->delegtxt = bab_translate("Delegation");
			$this->delegdesctxt = bab_translate("Description");
			$this->delegadmintxt = bab_translate("Managing administrators");
			$this->memberstxt = bab_translate("Managing administrators");
			$this->grpmtxt = bab_translate("Managed groups");
			$this->res = $res;
			$this->count = $babDB->db_num_rows($this->res);
			$this->c= 0;
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->delegval = $arr['description'];
				$this->urltxt = $arr['name'];
				$this->url = $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$arr['id'];
				$this->urlmem = $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$arr['id'];
				$rgroup = $babDB->db_query("select name from ".BAB_GROUPS_TBL." where id_dggroup=".$arr['id']);
				$this->count_g[$this->c] = 0;
				while ($arr = $babDB->db_fetch_array($rgroup))
					{
					$this->groups_tbl[$this->c][] = $arr['name'];
					$this->count_g[$this->c]++;
					}
				$this->c++;
				$i++;
				return true;
				}
			else{
				return false;
				}
			}

		function getnextgroup()
			{
			global $babDB;
			static $j = 0;
			if( $j < $this->count_g[$this->c-1])
				{
				if ($j+1 < $this->count_g[$this->c-1]) $this->end = false;
				else $this->end = true;
				$this->grpmval = $this->groups_tbl[$this->c-1][$j];
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				return false;
				}
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
			$this->delusers = bab_translate("Delete users");
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
		var $count1 = 0;

		function temp($gname, $description)
			{
			$this->db = $GLOBALS['babDB'];
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->add = bab_translate("Add");
			$this->grp_members = bab_translate("Managed groups");
			$this->functions = bab_translate("Deputy functions");
			$this->none = bab_translate("None");
			$this->new = true;
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

			$req = "select * from ".BAB_GROUPS_TBL." where id > 2 and id_dgowner='".$GLOBALS['babBody']->currentAdmGroup."' and id_dggroup='0' order by id asc";
			$this->res2 = $this->db->db_query($req);
			$this->count2 = $this->db->db_num_rows($this->res2);
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

		function getnextgroup()
			{
			static $i = 0;
			
			if( $i < $this->count2)
				{
				$this->arrgroups = $this->db->db_fetch_array($this->res2);
				if($this->count1 > 0)
					{
					$this->db->db_data_seek($this->res1, 0);
					$this->arrgroups['select'] = "";
					for( $j = 0; $j < $this->count1; $j++)
						{
						$this->groups = $this->db->db_fetch_array($this->res1);
						if( $this->groups['id'] == $this->arrgroups['id'])
							{
							$this->arrgroups['select'] = "selected";
							break;
							}
						}
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
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
			$this->add = bab_translate("Modify");
			$this->delete = bab_translate("Delete");
			$this->alert_msg = bab_translate("It is necessary to remove all associations with the users groups");
			$this->grp_members = bab_translate("Managed groups");
			$this->functions = bab_translate("Deputy functions");
			$this->none = bab_translate("None");
			$db = $GLOBALS['babDB'];
			$this->db = $db;
			$res = $db->db_query("select * from ".BAB_DG_GROUPS_TBL." where id='".$id."'");
			$this->arr = $db->db_fetch_array($res);
			$this->id = $id;


			list($total) = $db->db_fetch_row($db->db_query("select count(id) as total from ".BAB_GROUPS_TBL." where id_dggroup='".$id."'"));
			if( $total > 0 )
				{
				$this->bdel = false;
				$this->control = true; }
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

			$req = "select * from ".BAB_GROUPS_TBL." where id_dggroup='".$id."'";
			$this->res1 = $this->db->db_query($req);
			$this->count1 = $this->db->db_num_rows($this->res1);

			$req = "select * from ".BAB_GROUPS_TBL." where id > 2 and id_dgowner='".$GLOBALS['babBody']->currentAdmGroup."' and (id_dggroup='0' or id_dggroup='".$id."') order by id asc";
			$this->res2 = $this->db->db_query($req);
			$this->count2 = $this->db->db_num_rows($this->res2);
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

		function getnextgroup()
			{
			static $i = 0;
			
			if( $i < $this->count2)
				{
				$this->arrgroups = $this->db->db_fetch_array($this->res2);
				if($this->count1 > 0)
					{
					$this->db->db_data_seek($this->res1, 0);
					$this->arrgroups['select'] = "";
					for( $j = 0; $j < $this->count1; $j++)
						{
						$this->groups = $this->db->db_fetch_array($this->res1);
						if( $this->groups['id'] == $this->arrgroups['id'])
							{
							$this->arrgroups['select'] = "selected";
							break;
							}
						}
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
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
			$this->message = bab_translate("Are you sure you want to delete this delegation group");
			list($this->title) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_DG_GROUPS_TBL." where id='".$id."'"));
			$this->warning = bab_translate("WARNING: This operation will delete delegation group and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=delegat&idx=list&id=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function addDelegatGroup($name, $description, $delegitems, $groups)
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
		$babBody->msgerror = bab_translate("This delegation group already exists");
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

		$babDB->db_query("update ".BAB_GROUPS_TBL." set id_dggroup='0' where id_dggroup='".$id."'");
		if (is_array($groups))
			{
			foreach($groups as $id_group)
				{
				$babDB->db_query("update ".BAB_GROUPS_TBL." set id_dggroup='".$id."' where id='".$id_group."'");
				}
			}
		}

	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
	exit;
	}

function modifyDelegatGroup($name, $description, $delegitems, $id, $groups)
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
		$babBody->msgerror = bab_translate("Group of delegation with the same name already exists!");
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

		$babDB->db_query("update ".BAB_GROUPS_TBL." set id_dggroup='0' where id_dggroup='".$id."'");
		if (is_array($groups))
			{
			foreach($groups as $id_group)
				{
				$babDB->db_query("update ".BAB_GROUPS_TBL." set id_dggroup='".$id."' where id='".$id_group."'");
				}
			}
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
			if( !addDelegatGroup($gname, $description, $delegitems, $groups ))
				{
				$idx = 'new';
				}
			else
				$idx = 'list';
			}
		else if( $add == 'mod' )
			{
			if(!modifyDelegatGroup($gname, $description, $delegitems, $id, $groups))
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
	case "bg":
		browseGroups_dg($cb);
		exit;
		break;
	case "gdel":
		deleteDelegatGroup($id);
		$babBody->title = bab_translate("Delete delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("gdel", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=delegat&idx=gdel&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "mem":
		groupDelegatMembers($id);
		$babBody->title = bab_translate("Administrators of delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "mod":
		if( !isset($gname))	$gname = '';
		if( !isset($description)) $description = '';
		groupDelegatModify($gname, $description, $id);
		$babBody->title = bab_translate("Modify delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "new":
		if( !isset($gname))	$gname = '';
		if( !isset($description)) $description = '';
		groupDelegatCreate($gname, $description);
		$babBody->title = bab_translate("Create delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;

	case "list":
	default:
		delgatList($dgres);
		$babBody->title = bab_translate("Delegations list");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>
