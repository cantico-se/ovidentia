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
include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";
include_once $GLOBALS['babInstallPath']."utilit/delegincl.php";
include_once $GLOBALS['babInstallPath']."utilit/topincl.php";



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
			$this->grpmtxt = bab_translate("Managed group");
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
				$this->grpmval = $GLOBALS['babBody']->getGroupPathName($arr['id_group'], NULL);
				$this->c++;
				$i++;
				return true;
				}
			else{
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
			$this->id = $id;
			$this->usertxt = bab_translate("User");
			$this->addtxt = bab_translate("Add");
			$this->fullname = bab_translate("Fullname");
			$this->delusers = bab_translate("Delete users");
			$this->res = $babDB->db_query("select * from ".BAB_DG_ADMIN_TBL." where id_dg=".$id);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->fullnameval = bab_getUserName($arr['id_user']);
				$this->userid = $arr['id_user'];
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



function groupDelegatModify($gname, $description, $id = '')
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
			$this->add = bab_translate("Record");
			$this->delete = bab_translate("Delete");
			$this->t_color = bab_translate("Color");
			$this->alert_msg = bab_translate("It is necessary to remove all associations with the users groups");
			$this->grp_members = bab_translate("Managed group");
			$this->functions = bab_translate("Deputy functions");
			$this->attachdesc = bab_translate("Assign/unassign a user");
			$this->none = bab_translate("None");
			$db = &$GLOBALS['babDB'];
			$this->db = &$db;
			$res = $db->db_query("select * from ".BAB_DG_GROUPS_TBL." where id='".$id."'");
			$this->arr = $db->db_fetch_array($res);
			$this->id = $id;

			if (!empty($this->id))
				{
				$this->idGrp = &$this->arr['id_group'];
				$this->bdel = true;
				$this->colorvalue = isset($_POST['color']) ? $_POST['color'] : $this->arr['color'] ;
				$battach = isset($_POST['battach']) ? $_POST['battach'] : $this->arr['battach'] ;
				}
			else
				{
				$this->idGrp = false;
				$this->bdel = false;
				$this->colorvalue = isset($_POST['color']) ? $_POST['color'] : '' ;
				$battach = isset($_POST['battach']) ? $_POST['battach'] : 'N' ;
				}
			if( $battach == 'Y' )
				{
				$this->battachchecked = 'checked';
				}
			else
				{
				$this->battachchecked = '';
				}



			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(NULL, '%s '.chr(160).' '.chr(160).' ');
			unset($this->groups[BAB_UNREGISTERED_GROUP]);
			$this->count2 = count($this->groups);

			
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
				switch($babDG[$i][0])
					{
					case 'users':
						$this->delegitemdesc = bab_translate("Create a new user");
						break;
					case 'groups':
						$this->delegitemdesc = bab_translate("Manage groups");
						break;
					default:
						$this->delegitemdesc = bab_toHtml($babDG[$i][1]);
						break;
					}
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
			
			if( list(,$this->arrgroups) = each($this->groups))
				{
				$this->arrgroups['select'] = "";
				if( $this->idGrp == $this->arrgroups['id'])
					{
					$this->arrgroups['select'] = "selected";
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
	global $babBody,$babDB;

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

			$this->t_delete_all = bab_translate("Delete all objects created in the delegation");
			$this->t_set_to_admin = bab_translate("Attach objects to all site");

			$this->t_confirm = bab_translate("Confirm");
			$this->id = $id;
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"delegat.html", "delegatdelete"));
	}


function addDelegatGroup($name, $description, $color, $battach, $delegitems)
	{
	global $babBody, $babDB;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( !isset($_POST['group']) || $_POST['group'] == 'NULL')
		{
		$babBody->msgerror = bab_translate("ERROR: You must indicate the delegated group !!");
		return false;
		}

	$res = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL." where name='".$babDB->db_escape_string($name)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This delegation group already exists");
		return false;
		}
	else
		{
		if( $battach != 'Y' )
			{
			$battach = 'N';
			}

		$req1 = "(name, description, color, battach";
		$req2 = "('" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($description). "', '" . $babDB->db_escape_string($color). "', '" . $babDB->db_escape_string($battach). "'";
		for( $i = 0; $i < count($delegitems); $i++)
			{
			$req1 .= ", ". $babDB->db_escape_string($delegitems[$i]);
			$req2 .= ", 'Y'";
			}

		$group = $_POST['group'] == 'NULL' ? 'NULL' : "'".$babDB->db_escape_string($_POST['group'])."'";
		
		$req1 .= ",id_group )";
		$req2 .= ", ".$group." )";
		$babDB->db_query("insert into ".BAB_DG_GROUPS_TBL." ".$req1." VALUES ".$req2);
		$id = $babDB->db_insert_id();

		if( !bab_addTopicsCategory($name, $description, 'Y', '', '', 0, $id ))
			{
			return false;
			}

		}
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
	exit;
	}

function modifyDelegatGroup($name, $description, $color, $battach, $delegitems, $id)
	{
	global $babBody, $babDB, $babDG;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}



	$res = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL." 
	where id!='".$babDB->db_escape_string($id)."' and name='".$babDB->db_escape_string($name)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("Group of delegation with the same name already exists!");
		return false;
		}
	else
		{
		if( $battach != 'Y' )
			{
			$battach = 'N';
			}
		$req = "update ".BAB_DG_GROUPS_TBL." set 
			name='".$babDB->db_escape_string($name)."', 
			description='".$babDB->db_escape_string($description)."', 
			color='".$babDB->db_escape_string($color)."',
			battach='".$babDB->db_escape_string($battach)."'";
		$cnt = count($delegitems);
		for( $i = 0; $i < count($babDG); $i++)
			{
			if( $cnt > 0 && in_array($babDG[$i][0], $delegitems))
				$req .= ", ". $babDG[$i][0]."='Y'";
			else
				$req .= ", ". $babDG[$i][0]."='N'";
			}

		$group = $_POST['group'] == 'NULL' ? 'NULL' : "'".$babDB->db_escape_string($_POST['group'])."'";

		$req .= ", id_group=".$group;

		$babDB->db_query($req ." where id='".$babDB->db_escape_string($id)."'");

		}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
	exit;
	}

function updateDelegatMembers()
{
	global $babBody;
	$db = &$GLOBALS['babDB'];

	if (!empty($_POST['nuserid']) && !empty($_POST['id']))
	{
	$res = $db->db_query("SELECT COUNT(*) FROM ".BAB_DG_ADMIN_TBL." WHERE id_dg='".$_POST['id']."' AND id_user='".$_POST['nuserid']."'");
	list($n) = $db->db_fetch_array($res);
	if ($n > 0)
		{
		$babBody->msgerror = bab_translate("The user is in the list");
		return false;
		}

	$db->db_query("INSERT INTO ".BAB_DG_ADMIN_TBL." (id_dg,id_user) VALUES ('".$_POST['id']."','".$_POST['nuserid']."')");
	return true;
	}
	
}

function deleteDelegatMembers()
{
	$db = &$GLOBALS['babDB'];

	if (isset($_POST['users']) && count($_POST['users']) > 0 && !empty($_POST['id']))
	{
	$db->db_query("DELETE FROM ".BAB_DG_ADMIN_TBL." WHERE id_dg='".$_POST['id']."' AND id_user IN('".implode("','",$_POST['users'])."')");
	}
}

function confirmDeleteDelegatGroup($id)
{
	global $babDB;

	$idsafe = $babDB->db_escape_string($id);
	
	if( 0 == $_POST['doaction'] )
		{
		include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
		include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
		$res = $babDB->db_query("select id from ".BAB_SECTIONS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteSection($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteTopicCategory($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteApprobationSchema($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteForum($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_FAQCAT_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteFaq($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteFolder($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_LDAP_DIRECTORIES_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteLdapDirectory($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteDbDirectory($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_ORG_CHARTS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteOrgChart($arr['id']);
			}

		$res = $babDB->db_query("select crt.id, ct.id as idcal from ".BAB_CAL_PUBLIC_TBL." crt left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id and ct.type='".BAB_CAL_PUB_TYPE."' where crt.id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteCalendar($arr['idcal']);
			$babDB->db_query("delete from ".BAB_CAL_PUBLIC_TBL." where id='".$arr['id']."'");	
			}

		$res = $babDB->db_query("select crt.id, ct.id as idcal from ".BAB_CAL_RESOURCES_TBL." crt left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id and ct.type='".BAB_CAL_RES_TYPE."' where crt.id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteCalendar($arr['idcal']);
			$babDB->db_query("delete from ".BAB_CAL_RESOURCES_TBL." where id='".$arr['id']."'");	
			}
		}
	else
		{
		$babDB->db_query("update ".BAB_SECTIONS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_TOPICS_CATEGORIES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_FLOW_APPROVERS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_FORUMS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_FAQCAT_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_FM_FOLDERS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_LDAP_DIRECTORIES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_DB_DIRECTORIES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_ORG_CHARTS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_CAL_RESOURCES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		$babDB->db_query("update ".BAB_CAL_PUBLIC_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");	
		}



	$babDB->db_query("delete from ".BAB_DG_ADMIN_TBL." where id_dg='".$idsafe."'");
	$babDB->db_query("delete from ".BAB_DG_GROUPS_TBL." where id='".$idsafe."'");
	$babDB->db_query("delete from ".BAB_DG_ACL_GROUPS_TBL." where id_object='".$idsafe."'");
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
		if( $add == 'mod' )
			{
			if (!empty($_POST['id']))
				{
				if(!modifyDelegatGroup($_POST['gname'], $_POST['description'], $_POST['color'],	(isset($_POST['battach'])? $_POST['battach']: 'N'), (isset($_POST['delegitems'])? $_POST['delegitems']: array()), $_POST['id']))
					$idx = "mod";
				else
					$idx = 'list';
				}
			else
				{
				if( !addDelegatGroup($_POST['gname'], $_POST['description'], $_POST['color'],	(isset($_POST['battach'])? $_POST['battach']: 'N'), (isset($_POST['delegitems'])? $_POST['delegitems']: array())))
					$idx = 'new';
				else
					$idx = 'list';
				}
			}

		}
	else if( isset($deleteg) )
		{
		$idx = "gdel";
		}
	}


if (isset($_POST['action']))
switch($_POST['action'])
	{
	case 'add':
		updateDelegatMembers();
		break;
	case 'del':
		deleteDelegatMembers();
		break;
	case 'delete':
		confirmDeleteDelegatGroup($_POST['id']);
		$idx = 'list';
		break;
	}



if( $idx == 'list' )
{
	$dgres = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL."");
	if( !$dgres || $babDB->db_num_rows($dgres) == 0 )
		$idx = 'new';
}

if( isset($aclupdate))
	{
	include_once $babInstallPath.'admin/acl.php';
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
	}


switch($idx)
	{
	case "bg":
		browseGroups_dg($cb);
		exit;
		break;
	case "acl":
		include_once $babInstallPath.'admin/acl.php';
		$babBody->title = bab_translate("ACL delegation");
		$macl = new macl("delegat", "Modify", $id, "aclupdate");
        $macl->addtable( BAB_DG_ACL_GROUPS_TBL,bab_translate("ACL to use with this delegation"));
        $macl->babecho();
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("gdel", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=delegat&idx=gdel&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("acl", bab_translate("ACL"), $GLOBALS['babUrlScript']."?tg=delegat&idx=acl&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "gdel":
		deleteDelegatGroup($id);
		$babBody->title = bab_translate("Delete delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$id);
		$babBody->addItemMenu("gdel", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=delegat&idx=gdel&id=".$id);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$id);
		$babBody->addItemMenu("acl", bab_translate("ACL"), $GLOBALS['babUrlScript']."?tg=delegat&idx=acl&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "mem":
		groupDelegatMembers($_REQUEST['id']);
		$babBody->title = bab_translate("Administrators of delegation");
		$babBody->addItemMenu("list", bab_translate("Delegations"), $GLOBALS['babUrlScript']."?tg=delegat&idx=list");
		$babBody->addItemMenu("mod", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mod&id=".$_REQUEST['id']);
		$babBody->addItemMenu("mem", bab_translate("Managing administrators"), $GLOBALS['babUrlScript']."?tg=delegat&idx=mem&id=".$_REQUEST['id']);
		$babBody->addItemMenu("acl", bab_translate("ACL"), $GLOBALS['babUrlScript']."?tg=delegat&idx=acl&id=".$id);
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
		$babBody->addItemMenu("acl", bab_translate("ACL"), $GLOBALS['babUrlScript']."?tg=delegat&idx=acl&id=".$id);
		$babBody->addItemMenu("new", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=delegat&idx=new");
		break;
	case "new":
		if( !isset($gname))	$gname = '';
		if( !isset($description)) $description = '';
		groupDelegatModify($gname, $description);
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
