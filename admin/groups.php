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
include_once $babInstallPath."utilit/grptreeincl.php";

function groupCreateMod()
	{
	global $babBody;
	class CreateMod
		{
		var $name;
		var $description;
		var $managertext;
		var $useemail;
		var $no;
		var $yes;
		var $add;
		var $usersbrowurl;
		var $grpid;
		var $noselected;
		var $yesselected;
		var $tgval;
		var $grpdgtxt;
		var $grpdgid;
		var $grpdgname;
		var $count;
		var $res;
		var $selected;
		var $bdggroup;


		function CreateMod()
			{
			global $babBody;
			$this->t_name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->managertext = bab_translate("Manager");
			$this->useemail = bab_translate("Use email");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->t_record = bab_translate("Record");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->grpdgtxt = bab_translate("Delegation group");
			$this->t_parent = bab_translate("Parent");
			$this->t_delete = bab_translate("Delete");
			$this->t_ovidentia_users = bab_translate("Ovidentia users");
			$this->db = &$GLOBALS['babDB'];
			$this->bdggroup = false;
			$this->bdel =false;
			$this->maingroup = false;

			if (isset($_REQUEST['id_group']))
				{
				$req = "select * from ".BAB_GROUPS_TBL." where id='".$_REQUEST['id_group']."'";
				$res = $this->db->db_query($req);
				$this->arr = $this->db->db_fetch_array($res);

				$this->arr['managerval'] = bab_getUserName($this->arr['manager']);

				if ($this->arr['id'] < 3)
					{
					$this->maingroup = true;
					}
				elseif ($this->arr['id'] > 3)
					{
					$this->bdel =true;
					}
				}

			else
				{
				$this->arr = array(
						'id' => '',
						'name' => '',
						'description' => '',
						'manager' => 0,
						'managerval' => '',
						'id_parent' => BAB_REGISTERED_GROUP,
						'id_dggroup' => 0
					);

				}


			if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
				{
				$this->res = $this->db->db_query("select * from ".BAB_DG_GROUPS_TBL."");
				$this->count = $this->db->db_num_rows($this->res);
				if( $this->count > 0 )
					$this->bdggroup = true;
				}
			

			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(0, '&nbsp; &nbsp; &nbsp; %2$s');
			unset($this->groups[BAB_UNREGISTERED_GROUP]);
			}

		function getnextgroup()
			{
			if (list($this->id, $this->name) = each($this->groups))
				{
				$this->selected = $this->id == $this->arr['id_parent'];
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnext()
			{
			static $i = 0;	
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->grpdgname = $arr['name'];
				$this->grpdgid = $arr['id'];
				$this->selected = $this->grpdgid == $this->arr['id_dggroup'];
				$i++;
				return true;
				}
			return false;
			}


		}

	$temp = new CreateMod();
	$babBody->babecho(	bab_printTemplate($temp,"groups.html", "groupscreate"));
	}


class bab_grp_node
{
	function bab_grp_node(&$tree, $id_group)
	{
	$this->t_group_set_d = bab_translate("Users group with delegation and group set associated");
	$this->t_group_d = bab_translate("Users group with delegation");
	$this->t_group_set = bab_translate("Group associated with one or more sets of groups");
	$this->t_group = bab_translate("Users group");
	$this->tree = &$tree;
	$this->childs = $tree->getChilds($id_group);
	}

	function getnextgroup()
	{
	if ($this->childs && list(,$this->arr) = each($this->childs))
		{
		if ($this->arr['id'] < 4)
			{
			$this->arr['name'] = bab_translate($this->arr['name']);
			$this->arr['description'] = bab_translate($this->arr['description']);
			}
		$this->arr['description'] = htmlentities($this->arr['description']);
		$this->arr['managerval'] = htmlentities(bab_getUserName($this->arr['manager']));
		$this->subtree = bab_grp_node_html($this->tree, $this->arr['id']);
		return true;
		}
	else 
		{
		return false;
		}
	}
}

function bab_grp_node_html(&$tree, $id_group)
{
	$temp = & new bab_grp_node($tree, $id_group);
	if ($temp->childs)
		return bab_printTemplate($temp, "groups.html", "grp_childs");
	else return '';
}


function groupList()
	{

	global $babBody;

	class temp
		{
		function temp()
			{
			$this->t_expand_all = bab_translate("Expand all");
			$this->t_collapse_all = bab_translate("Collapse all");
			$this->t_newgroup = bab_translate("New group");
			$this->t_group = bab_translate("Main groups folder");
			$tree = & new bab_grptree();
			$this->arr = $tree->getNodeInfo(0);
			$this->arr['name'] = bab_translate($this->arr['name']);
			$this->arr['description'] = htmlentities(bab_translate($this->arr['description']));
			$this->tpl_tree = bab_grp_node_html($tree, 0);
			}
		}

	$temp = new temp();
	$babBody->addStyleSheet('groups.css');
	$babBody->babecho(bab_printTemplate($temp, "groups.html", "grp_maintree"));
	}

function groupsOptions()
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $mail;
		var $notes;
		var $contacts;
		var $directory;
		var $url;
		var $urlname;
		var $group;
			
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $burl;
		var $persdiskspace;

		var $bdgmail;
		var $bpcalendar;
		var $bdgnotes;
		var $bdgcontacts;
		var $bdgdirectories;
		var $bdgpds;
		var $altbg = true;

		function temp()
			{
			global $babBody;
			$this->fullname = bab_translate("Groups");
			$this->mail = bab_translate("Mail");
			$this->notes = bab_translate("Notes");
			$this->contacts = bab_translate("Contacts");
			$this->persdiskspace = bab_translate("Personal disk space");
			$this->directory = bab_translate("Site directory");
			$this->modify = bab_translate("Update");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");

			if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
				{
				$this->bdgmail = true;
				$this->bdgnotes = true;
				$this->bdgcontacts = true;
				$this->bdgdirectories = true;
				$this->bdgpds = true;
				}
			else
				{
				if( $babBody->currentDGGroup['mails'] == 'Y' )
					$this->bdgmail = true;
				else
					$this->bdgmail = false;

				$this->bdgnotes = true;
				$this->bdgcontacts = true;

				if( $babBody->currentDGGroup['directories'] == 'Y' )
					$this->bdgdirectories = true;
				else
					$this->bdgdirectories = false;

				if( $babBody->currentDGGroup['filemanager'] == 'Y' )
					$this->bdgpds = true;
				else
					$this->bdgpds = false;
				}
			$req = "select * from ".BAB_GROUPS_TBL." where id!='2' and id_dgowner='".$babBody->currentAdmGroup."' order by name asc";
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->altbg = false;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->burl = true;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->grpid = $this->arr['id'];

				if( $this->arr['mail'] == "Y")
					$this->mailcheck = "checked";
				else
					$this->mailcheck = "";
				if( $this->arr['notes'] == "Y")
					$this->notescheck = "checked";
				else
					$this->notescheck = "";
				if( $this->arr['contacts'] == "Y")
					$this->concheck = "checked";
				else
					$this->concheck = "";
				if( $this->arr['ustorage'] == "Y")
					$this->pdscheck = "checked";
				else
					$this->pdscheck = "";

				if( $this->arr['directory'] == "Y")
					$this->dircheck = "checked";
				else
					$this->dircheck = "";
				if( $this->arr['id'] < 3 )
					{
					$this->urlname = bab_getGroupName($this->arr['id']);
					}
				else
					{
					$this->urlname = $this->arr['name'];
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "groups.html", "groupsoptions"));
	}

function addModGroup()
	{
	global $babBody;
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";

	if ( is_numeric($_POST['idgrp']) )
		{

		}
	else
		{
		$id = bab_addGroup($name, $description, $managerid, $grpdg);
		}

	if( !$id)
		{
		return false;
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$id);
	return true;
	}

function saveGroupsOptions($mailgrpids, $notgrpids, $congrpids, $pdsgrpids, $dirgrpids)
{

	global $babBody;

	$db = &$GLOBALS['babDB'];

	$db->db_query("update ".BAB_GROUPS_TBL." set mail='N', notes='N', contacts='N', ustorage='N', directory='N' where  id_dgowner='".$babBody->currentAdmGroup."'"); 

	for( $i=0; $i < count($mailgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set mail='Y' where id='".$mailgrpids[$i]."'"); 
	}

	for( $i=0; $i < count($notgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set notes='Y' where id='".$notgrpids[$i]."'"); 
	}

	for( $i=0; $i < count($congrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set contacts='Y' where id='".$congrpids[$i]."'"); 
	}

	for( $i=0; $i < count($pdsgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set ustorage='Y' where id='".$pdsgrpids[$i]."'"); 
	}

	$grpdirectories = array();

	for( $i=0; $i < count($dirgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set directory='Y' where id='".$dirgrpids[$i]."'");

		$res = $db->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$dirgrpids[$i]."'");
		if( !$res || $db->db_num_rows($res) == 0 )
		{
			$db->db_query("insert into ".BAB_DB_DIRECTORIES_TBL." (name, description, id_group, id_dgowner) values ('".$db->db_escape_string(bab_getGroupName($dirgrpids[$i]))."','','".$dirgrpids[$i]."', '".$babBody->currentAdmGroup."')");

			$id = $db->db_insert_id($res);
		}
		else
		{
		list($id) = $db->db_fetch_array($res);
		}

		$grpdirectories[] = $id;
	}
	$grpdirectories[] = 0;
	$grpdirectories[] = 1;
	
	$db->db_query("DELETE FROM ".BAB_DB_DIRECTORIES_TBL." where id_group NOT IN('".implode("','",$grpdirectories)."')");	

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=options");
	exit;
}

/* main */
if( !isset($idx))
	$idx = "List";

if( isset($_POST['add']) && ($babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y'))
	{
	addModGroup();
	}

if( isset($update) && $update == "options" && ($babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y'))
	{
	if (!isset($mailgrpids)) $mailgrpids = array();
	if (!isset($notgrpids)) $notgrpids = array();
	if (!isset($congrpids)) $congrpids = array();
	if (!isset($pdsgrpids)) $pdsgrpids = array();
	if (!isset($dirgrpids)) $dirgrpids = array();
	if (!isset($calperids)) $calperids = array();
	saveGroupsOptions($mailgrpids, $notgrpids, $congrpids, $pdsgrpids, $dirgrpids);
	}

switch($idx)
	{
	case "brow": // Used by add-ons 
		include_once $babInstallPath."utilit/grpincl.php";
		browseGroups($cb);
		exit;
		break;
	case "options":
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			groupsOptions();
			$babBody->title = bab_translate("Options");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
			$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case "Create":
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			groupCreateMod();
			$babBody->title = bab_translate("Create a group");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
			$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case "List":
	default:
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			groupList();
			groupCreateMod();
			$babBody->title = bab_translate("Groups list");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
			$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>