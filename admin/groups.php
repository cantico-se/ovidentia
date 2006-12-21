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
			$this->t_create_group = bab_translate("Create group");
			$this->t_edit_group = bab_translate("Edit group");
			$this->db = &$GLOBALS['babDB'];
			$this->bdggroup = false;
			$this->bdel = false;
			$this->maingroup = false;

			
			$tree = new bab_grptree();

			if ($babBody->currentAdmGroup > 0)
				{
				$id_parent = $tree->firstnode_info['id'];
				if ($id_parent > BAB_ALLUSERS_GROUP)
					{
					$id_parent = $tree->firstnode_info['id_parent'];
					}
				}
			else
				$id_parent = $tree->firstnode_info['id'];
			
			$this->groups = $tree->getGroups($id_parent, '%s &nbsp; &nbsp; &nbsp; ');

			if (isset($this->groups[BAB_UNREGISTERED_GROUP]))
				{
				unset($this->groups[BAB_UNREGISTERED_GROUP]);
				}

			if ($babBody->currentAdmGroup > 0)
				{
				unset($this->groups[$id_parent]);
				}

			if (isset($_POST['grpid']))
				{
				$this->arr = array(
						'id' => $_POST['grpid'],
						'name' => $_POST['name'],
						'description' => $_POST['description'],
						'manager' => $_POST['manager'],
						'managerval' => bab_getUserName($_POST['manager']),
						'id_parent' => $_POST['parent']
					);

				}
			elseif(isset($_REQUEST['grpid']))
				{
				unset($this->groups[$_REQUEST['grpid']]);

				$req = "select * from ".BAB_GROUPS_TBL." where id='".$_REQUEST['grpid']."'";
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
						'id_parent' => BAB_REGISTERED_GROUP
					);

				}
			
			}

		function getnextgroup()
			{
			if (list($this->id, $arr) = each($this->groups))
				{
				$this->name = $arr['name'];
				$this->selected = $this->id == $this->arr['id_parent'];
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new CreateMod();
	$babBody->babecho(	bab_printTemplate($temp,"groups.html", "groupscreate"));
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
			$this->t_create_group = bab_translate("Create group");
			$this->t_edit_group = bab_translate("Edit group");
			$this->t_members = bab_translate("Members");
			$tree = & new bab_grptree();
			$this->arr = $tree->getNodeInfo($tree->firstnode);
			$this->arr['name'] = bab_translate($this->arr['name']);
			$this->arr['description'] = htmlentities(bab_translate($this->arr['description']));
			$this->arr['managerval'] = htmlentities(bab_getUserName($this->arr['manager']));
			$this->delegat = $GLOBALS['babBody']->currentAdmGroup == 0 && isset($tree->delegat[$this->arr['id']]);
			$this->tpl_tree = bab_grp_node_html($tree, $tree->firstnode, 'groups.html', 'grp_childs');

			$this->indelegat = $GLOBALS['babBody']->currentAdmGroup > 0;
			$this->bupdate = $GLOBALS['babBody']->isSuperAdmin || $GLOBALS['babBody']->currentDGGroup['groups'] == 'Y';

			if (isset($_REQUEST['expand_to']))
				{
				$this->id_expand_to = &$_REQUEST['expand_to'];
				}
			else
				{
				if ($GLOBALS['babBody']->currentAdmGroup > 0)
					{
					$firstchild = $tree->getFirstChild($tree->firstnode);
					if ($firstchild) {
						$this->id_expand_to = $firstchild['id'];
						}
					}
				else
					{
					$this->id_expand_to = BAB_ADMINISTRATOR_GROUP;
					}
				}
			
			}
		}

	$temp = new temp();
	$babBody->addStyleSheet('groups.css');
	$babBody->babecho(bab_printTemplate($temp, "groups.html", "grp_maintree"));
	}


function moveGroup()
	{
	global $babBody;

	class temp
		{
		function temp()
			{
			$this->arr = $_POST;

			$this->t_name = bab_translate("Name");
			$this->t_record = bab_translate("Record");
			$this->t_move_group = bab_translate("Move the group only");
			$this->t_move_group_childs = bab_translate("Move the group and his children");
			}
		}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "groups.html", "moveGroup"));
	}


function groupDelete($id)
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
			$this->message = bab_translate("Are you sure you want to delete this group");
			$this->title = bab_getGroupName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the group with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=group&idx=Delete&group=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=groups";
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho( bab_printTemplate($temp,"warning.html", "warningyesno"));
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
			
			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(BAB_ALLUSERS_GROUP);
			unset($this->groups[BAB_UNREGISTERED_GROUP]);					
			}

		function getnext()
			{
			if (list(,$this->arr) = each($this->groups))
				{
				$this->altbg = !$this->altbg;
				$this->burl = true;
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

				$this->urlname = $this->arr['name'];
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
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";

	global $babBody;
	$db = &$GLOBALS['babDB'];

	$id_parent = &$_POST['parent'];
	$grpdg = isset($_POST['grpdg']) ? $_POST['grpdg'] : 0;

	if ( !is_numeric($_POST['grpid']) )
		{
		$ret = bab_addGroup($_POST['name'], $_POST['description'], $_POST['manager'], $grpdg, $id_parent);
		if ($ret)
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=List&expand_to=".$ret);
		else
			return 'Create';
		}

	if( empty($_POST['name']))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return 'Create';
		}


	$description = $db->db_escape_string($_POST['description']);
	$name = $db->db_escape_string($_POST['name']);


	$req = "select * from ".BAB_GROUPS_TBL." where name='".$name."' AND id_parent='".$db->db_escape_string($id_parent)."'";
	if (is_numeric($_POST['grpid']) )
		{
		$req .= " AND id != '".$db->db_escape_string($_POST['grpid'])."'";
		}
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This group already exists");
		return 'Create';
		}


	// move group ?

	if (!isset($_POST['moveoption']) && $_POST['grpid'] > BAB_UNREGISTERED_GROUP)
		{
		$res = $db->db_query("select id_parent, (lr-lf) groups from ".BAB_GROUPS_TBL." where id='".$db->db_escape_string($_POST['grpid'])."'");
		$arr = $db->db_fetch_assoc($res);

		if ($arr['id_parent'] != $id_parent && $arr['groups'] > 1)
			{
			return 'move';
			}
		else $moveoption = 1;

		}
	else
		{
		$moveoption = isset($_POST['moveoption']) ? $_POST['moveoption'] : 1;
		}
	

	$idgrp = &$_POST['grpid'];
	bab_updateGroupInfo($idgrp, $name, $description, $_POST['manager'], $grpdg );
	bab_moveGroup($idgrp, $id_parent, $moveoption, stripslashes($name));

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=List&expand_to=".$idgrp);
	return $_POST['idx'];
	}

function saveGroupsOptions($mailgrpids, $notgrpids, $congrpids, $pdsgrpids, $dirgrpids)
{

	global $babBody;

	$db = &$GLOBALS['babDB'];

	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");

	if ($babBody->currentAdmGroup > 0)
		{
		return false;
		$db->db_query("update ".BAB_GROUPS_TBL." set mail='N', notes='N', contacts='N', ustorage='N', directory='N' where  lf>'".$babBody->currentDGGroup['lf']."' AND lr<'".$babBody->currentDGGroup['lr']."'");
		}
	else
		{
		$db->db_query("update ".BAB_GROUPS_TBL." set mail='N', notes='N', contacts='N', ustorage='N', directory='N'");
		}

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


	for( $i=0; $i < count($dirgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set directory='Y' where id='".$dirgrpids[$i]."'");

		$res = $db->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$dirgrpids[$i]."'");
		if( !$res || $db->db_num_rows($res) == 0 )
		{
			$db->db_query("insert into ".BAB_DB_DIRECTORIES_TBL." (name, description, id_group, id_dgowner) values ('".$db->db_escape_string(bab_getGroupName($dirgrpids[$i], false))."','','".$dirgrpids[$i]."', '".$babBody->currentAdmGroup."')");
		}
		
	}
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=options");
	exit;
}

/* main */

if (!$babBody->isSuperAdmin && $babBody->currentDGGroup['groups'] != 'Y' ) {
	$babBody->addError('Access denied');
	return;
}



$idx = bab_rp('idx','List');

if( isset($_POST['add']))
	{
	if (isset($_POST['deleteg']))
		{
		$item = $_POST['grpid'];
		$idx = 'Delete';
		}
	else
		{
		$idx = addModGroup();
		}
	}

if( isset($update) && $update == "options")
	{
	if (!isset($mailgrpids)) $mailgrpids = array();
	if (!isset($notgrpids)) $notgrpids = array();
	if (!isset($congrpids)) $congrpids = array();
	if (!isset($pdsgrpids)) $pdsgrpids = array();
	if (!isset($dirgrpids)) $dirgrpids = array();
	if (!isset($calperids)) $calperids = array();
	saveGroupsOptions($mailgrpids, $notgrpids, $congrpids, $pdsgrpids, $dirgrpids);
	}

if ($idx != "brow")
	{
	$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
	if (0 == $babBody->currentAdmGroup)
		{
		$babBody->addItemMenu("sets", bab_translate("Sets of Group"), $GLOBALS['babUrlScript']."?tg=setsofgroups&idx=list");
		$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
		$babBody->addItemMenu("plist", bab_translate("Profiles"), $GLOBALS['babUrlScript']."?tg=profiles&idx=plist");
		}

	}

switch($idx)
	{
	case "brow": 
		// Used by add-ons and deprecated after 6.1.0 for security reasons
		// user must be admin
		include_once $babInstallPath."utilit/grpincl.php";
		browseGroups(bab_gp('cb'));
		exit;
		break;
	case "options":
		groupsOptions();
		$babBody->title = bab_translate("Options");
		break;
	case "Create":
		groupCreateMod();

		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		if (!empty($_REQUEST['grpid']))
			{
			$babBody->title = bab_translate("Modify a group");
			$babBody->addItemMenu("Create", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
			}
		else
			{
			$babBody->title = bab_translate("Create a group");
			$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
			}
		break;
	case "Delete":
		if( $item > 3 )
			groupDelete($item);
		$babBody->title = bab_translate("Delete group");
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=group&idx=Delete&item=".$item);
		break;
	case "move":
		moveGroup();
		$babBody->title = bab_translate("Move group");
		break;

	case 'treecreate':
		if ($babBody->isSuperAdmin)	
			bab_grpTreeCreate(NULL, 1);
		die();

	case "List":
	default:
		groupList();
		groupCreateMod();
		$babBody->title = bab_translate("Groups list");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>