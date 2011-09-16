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
include_once $babInstallPath."admin/mgroup.php";

function setOfGroupsName($sid)
{
	$db = &$GLOBALS['babDB'];
	$req = "SELECT name FROM ".BAB_GROUPS_TBL." WHERE id='".$db->db_escape_string($sid)."' AND nb_groups>='0'";
	$arr = $db->db_fetch_assoc($db->db_query($req));
	return $arr['name'];
}

function slist()
	{
	global $babBody;
	class temp
		{
		var $altbg = true;

		function temp()
			{
			global $babBody;
			$this->db = &$GLOBALS['babDB'];
			$this->t_name = bab_translate("Name");
			$this->t_groups = bab_translate("groups");
			$this->t_new = bab_translate("New");
			$this->t_modify = bab_translate("Modify");
			$this->t_add_group = bab_translate("Add");
			$this->t_create_set = bab_translate("Create a set of groups");
			$this->t_edit_set = bab_translate("Modify the set of groups");

			$this->res = $this->db->db_query("SELECT * FROM ".BAB_GROUPS_TBL." WHERE nb_groups>='0'");

			}

		function getnext()
			{
			if( $this->arr = $this->db->db_fetch_array($this->res))
				{
				$this->t_group = 1 < $this->arr['nb_groups'] ? bab_translate("groups") : bab_translate("group");
				$this->altbg = !$this->altbg;
				return true;
				}
			return false;
			}


		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"setsofgroups.html", "slist"));
	}


function glist()
	{
	global $babBody;
	class temp
		{
		var $altbg = true;

		function temp()
			{
			$this->db = &$GLOBALS['babDB'];
			$this->t_name = bab_translate("Name");
			$this->t_del = bab_translate("Delete");
			$this->t_add_groups = bab_translate("Add groups");
			$this->t_update = bab_translate("Delete");
			$this->t_update = bab_translate("Update");
			$this->confirmdelete = bab_translate("Do you really want to delete the selected items ?");
			$this->sid = bab_rp('sid');

			$this->res = $this->db->db_query("SELECT g.* FROM ".BAB_GROUPS_SET_ASSOC_TBL." a, ".BAB_GROUPS_TBL." g WHERE a.id_set='".$this->db->db_escape_string($_REQUEST['sid'])."' AND g.id=a.id_group");

			}

		function getnext()
			{
			global $babBody;
			if( $this->arr = $this->db->db_fetch_array($this->res))
				{
				$this->arr['name'] = $babBody->getGroupPathName($this->arr['id']);
				$this->altbg = !$this->altbg;
				return true;
				}
			return false;
			}


		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"setsofgroups.html", "glist"));
	}



function sedit()
	{
	global $babBody;
	class sedittemp
		{

		function sedittemp()
			{
			global $babBody;
			$this->db = &$GLOBALS['babDB'];
			$this->t_name = bab_translate("Name");
			$this->t_record = bab_translate("Record");
			$this->t_delete = bab_translate("Delete");
			$this->t_delconf = bab_translate("Do you really want to delete the set of groups ?");
			$this->t_create_set = bab_translate("Create a set of groups");

			if (isset($_REQUEST['sid']))
				{
				$this->arr = $this->db->db_fetch_array($this->db->db_query("SELECT * FROM ".BAB_GROUPS_TBL." WHERE id='".$this->db->db_escape_string($_REQUEST['sid'])."' AND nb_groups>='0'"));
				$this->bdel = true;
				}

			if (empty($this->arr))
				{
				$this->arr = array(
						'id' => 0,
						'name' => '',
						'description' =>''
					);
				$this->bdel = false;
				}
			}
		}

	$temp = new sedittemp();
	$babBody->babecho(	bab_printTemplate($temp,"setsofgroups.html", "sedit"));
	}


function getGroupsFromSet($ids)
{
	$db = &$GLOBALS['babDB'];
	$groups = array();
	$res = $db->db_query("SELECT id_group FROM ".BAB_GROUPS_SET_ASSOC_TBL." WHERE id_set='".$db->db_escape_string($ids)."'");
	while ($arr = $db->db_fetch_assoc($res))
		{
		$groups[$arr['id_group']] = $arr['id_group'];
		}
	return $groups;
}


function sedit_record()
{


	global $babBody;
	$db = &$GLOBALS['babDB'];

	if (empty($_POST['name']))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name");
		return false;
		}

	list($n) = $db->db_fetch_array($db->db_query("SELECT COUNT(*) FROM ".BAB_GROUPS_TBL." WHERE name='".$db->db_escape_string($_POST['name'])."' AND nb_groups>='0'"));
	if ($n > 0)
		{
		$babBody->msgerror = bab_translate("This set of groups already exists");
		return false;
		}

	if (empty($_POST['sid']))
		{
		include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
		$node_id = getNextAvariableId();

		$db->db_query("INSERT INTO ".BAB_GROUPS_TBL." (id,name,nb_groups) VALUES ('".$db->db_escape_string($node_id)."','".$db->db_escape_string($_POST['name'])."',0)");
		}
	else
		{
		$db->db_query("UPDATE ".BAB_GROUPS_TBL." SET name='".$db->db_escape_string($_POST['name'])."' WHERE id='".$db->db_escape_string($_POST['sid'])."'");
		}

	return true;
}


function sedit_delete()
{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteSetOfGroup($_POST['sid']);
}

function record_setOfGroups($arr)
{
	$db = &$GLOBALS['babDB'];
	$current = getGroupsFromSet($_POST['sid']);

	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");

	foreach ($arr as $idgroup)
		{
		if (!isset($current[$idgroup]))
			{
			$db->db_query("INSERT INTO ".BAB_GROUPS_SET_ASSOC_TBL." (id_group, id_set) VALUES ('".$db->db_escape_string($idgroup)."','".$db->db_escape_string($_POST['sid'])."')");
			$db->db_query("UPDATE ".BAB_GROUPS_TBL." SET nb_set=nb_set+'1' WHERE id='".$db->db_escape_string($idgroup)."'");
			}
		unset($current[$idgroup]);
		}

	$db->db_query("UPDATE ".BAB_GROUPS_TBL." SET nb_groups='".$db->db_escape_string(count($arr))."' WHERE id='".$db->db_escape_string($_POST['sid'])."' AND nb_groups>= '0'");

	if (count($current) > 0)
		{
		$db->db_query("DELETE FROM ".BAB_GROUPS_SET_ASSOC_TBL." WHERE id_set='".$db->db_escape_string($_POST['sid'])."' AND id_group IN(".$db->quote($current).")");
		$db->db_query("UPDATE ".BAB_GROUPS_TBL." SET nb_set=nb_set-'1' WHERE id IN(".$db->quote($current).")");
		}
}

function delete_glist()
{
	$db = &$GLOBALS['babDB'];
	if (isset($_POST['groups']) && count($_POST['groups']) > 0)
		{
		$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");

		$db->db_query("DELETE FROM ".BAB_GROUPS_SET_ASSOC_TBL." WHERE id_set='".$db->db_escape_string($_POST['sid'])."' AND id_group IN('".implode("','",$_POST['groups'])."')");

		$db->db_query("UPDATE ".BAB_GROUPS_TBL." SET nb_set=nb_set-'1' WHERE id IN(".$db->quote($_POST['groups']).")");
		$db->db_query("UPDATE ".BAB_GROUPS_TBL." SET nb_groups=nb_groups-'".$db->db_escape_string(count($_POST['groups']))."' WHERE id='".$db->db_escape_string($_POST['sid'])."' AND nb_groups>='0'");
		}


}


// main

if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['groups'] != 'Y')
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
$babBody->addItemMenu("list", bab_translate("Sets of Group"), $GLOBALS['babUrlScript']."?tg=setsofgroups&idx=list");


$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : 'list';


if (isset($_POST['action']))
switch ($_POST['action'])
	{
	case 'sedit':
		if (isset($_POST['deleteg']))
			{
			sedit_delete();
			}
		else if (!sedit_record())
			$idx='sedit';
		break;

	case 'mgroups':
		$arr = mgroups_getSelected();
		record_setOfGroups($arr);
		break;

	case 'glist':
		delete_glist();
		break;
}





switch ($idx)
{
	case 'add':
		$babBody->addItemMenu("add", bab_translate("Add groups"), $GLOBALS['babUrlScript']."?tg=setsofgroups&idx=add");
		if (!empty($_REQUEST['sid']))
			{
			$babBody->title = bab_translate("Add groups in set").' '.setOfGroupsName($_REQUEST['sid']);

			$mgroups = new mgroups('setsofgroups','glist',BAB_REGISTERED_GROUP);
			$mgroups->setField('action', 'mgroups');
			$mgroups->setField('sid', $_REQUEST['sid']);
			$mgroups->setGroupOption(BAB_REGISTERED_GROUP,'disabled',true);
			$mgroups->setGroupsOptions(getGroupsFromSet($_REQUEST['sid']),'checked',true);
			$mgroups->babecho();
			}
		break;

	case 'glist':
		$babBody->addItemMenu("glist", bab_translate("Set of groups"), $GLOBALS['babUrlScript']."?tg=setsofgroups&idx=glist");
		$babBody->title = setOfGroupsName($_REQUEST['sid']);
		if (!empty($_REQUEST['sid']))
			glist();
		break;

	case 'sedit':
		$babBody->title = bab_translate("Set of groups");
		$babBody->addItemMenu("sedit", bab_translate("Edit"), $GLOBALS['babUrlScript']."?tg=setsofgroups&idx=sedit");
		sedit();
		break;

	case 'list':
		$babBody->title = bab_translate("Sets of groups");
		slist();
		sedit();
		break;
}

$babBody->setCurrentItemMenu($idx);

?>