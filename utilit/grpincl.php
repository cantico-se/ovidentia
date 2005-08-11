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

function browseGroups($cb)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $urlname;
		var $url;
				
		var $fullnameval;

		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $groupid;
		var $groupname;
		var $jgroupname;

		function temp($cb)
			{
			global $babBody;
			$this->db = $GLOBALS['babDB'];
			$this->cb = $cb;

			$this->fullname = bab_translate("Group");

			include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(BAB_REGISTERED_GROUP);
			}

		function getnext()
			{
			if (list(,$arr) = each($this->groups))
				{
				$this->groupid = $arr['id'];
				$this->groupname = $arr['name'];
				$this->jgroupname = str_replace("'", "\'", $this->groupname);
				$this->jgroupname = str_replace('"', "'+String.fromCharCode(34)+'",$this->jgroupname);
				return true;
				}
			else
				return false;

			}

		}

	$temp = new temp($cb);

	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = bab_translate("Group");
	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "groups.html", "browsegroups"));
	printBabBodyPopup();
	die();
	}

// used in add-ons from v4.08
function getGroupsMembers($id_grp)
	{
	if (is_array($id_grp))
		$id_grp = implode(",",$id_grp);
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("SELECT u.* FROM ".BAB_USERS_GROUPS_TBL." g, ".BAB_USERS_TBL." u WHERE g.id_group IN (".$id_grp.") AND g.id_object=u.id");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$i = 0;
		while ($arr = $db->db_fetch_array($res))
			{
			$user[$i]['id'] = $arr['id'];
			$user[$i]['name'] = bab_composeUserName($arr['firstname'],$arr['lastname']);
			$user[$i]['email'] = $arr['email'];
			$i++;
			}
		return $user;
		}
	else
		return false;
	}


function bab_updateGroupInfo($id, $name, $description, $managerid, $grpdg , $id_parent)
	{
	include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

	$tree = & new bab_grptree();
	$node = $tree->getNodeInfo($id);

	$db = &$GLOBALS['babDB'];

	$db->db_query("UPDATE ".BAB_GROUPS_TBL." 
			SET 
				name='".$name."', 
				description = '".$description."',
				manager = '".$managerid."'
			WHERE
				id='".$id."'
			");

	switch($id)
		{
		case 0:
			$id_parent = NULL;
			break;
		case 1:
		case 2:
			$id_parent = 0;
			break;
		}

	if ($node['id_parent'] != $id_parent)
		{
		$tree->moveAlpha($id, $id_parent, $name);
		}
	}


function bab_addGroup($name, $description, $managerid, $grpdg, $parent = 1)
	{
	
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return 0;
		}

	$db = &$GLOBALS['babDB'];

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$req = "select * from ".BAB_GROUPS_TBL." where name='$name'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This group already exists");
		return 0;
		}
	else
		{
		include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

		$tree = & new bab_grptree();
		$id = $tree->addAlpha($parent, $name);
		unset($tree);

		$db->db_query("UPDATE ".BAB_GROUPS_TBL." 
			SET 
				name='".$name."', 
				description = '".$description."',
				manager = '".$managerid."',
				nb_set = '0', 	
				mail = 'N', 
				ustorage = 'N', 
				notes = 'N', 
				contacts = 'N', 
				directory = 'N', 
				pcalendar = 'N'
			WHERE
				id='".$id."'
			");

		bab_callAddonsFunction('onGroupCreate', $id);
		return $id;
		}
	}


function confirmDeleteAdmGroup($id, $action)
	{
	global $babDB;

	if( $id <= 3)
		return;

	
	}


?>