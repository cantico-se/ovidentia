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
			$this->res = $this->db->db_query("select * from ".BAB_GROUPS_TBL." where id!='2' and id_dgowner='".$babBody->currentAdmGroup."' order by name asc");
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->groupid = $arr['id'];
				if( $arr['id'] < 3 )
					{
					$this->groupname = bab_getGroupName($arr['id']);
					}
				else
					{
					$this->groupname = $arr['name'];
					}
				$this->jgroupname = str_replace("'", "\'", $this->groupname);
				$this->jgroupname = str_replace('"', "'+String.fromCharCode(34)+'",$this->jgroupname);				
				$i++;
				return true;
				}
			else
				return false;

			}

		}

	$temp = new temp($cb);
	echo bab_printTemplate($temp, "groups.html", "browsegroups");
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
				manager = '".$managerid."',
				id_dggroup = '".$grpdg."'
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
				id_dggroup = '".$grpdg."'
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

	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	if( $action == 1 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
		$res = $babDB->db_query("select id from ".BAB_SECTIONS_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteSection($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteTopicCategory($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteApprobationSchema($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteForum($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_FAQCAT_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteFaq($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteFolder($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_LDAP_DIRECTORIES_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteLdapDirectory($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteDbDirectory($arr['id']);
			}

		$res = $babDB->db_query("select id from ".BAB_ORG_CHARTS_TBL." where id_dgowner='".$id."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			bab_deleteOrgChart($arr['id']);
			}
		}
	else
		{
		$db->db_query("update ".BAB_SECTIONS_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		$db->db_query("update ".BAB_TOPICS_CATEGORIES_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		$db->db_query("update ".BAB_FLOW_APPROVERS_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		$db->db_query("update ".BAB_FORUMS_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		$db->db_query("update ".BAB_FAQCAT_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		$db->db_query("update ".BAB_FM_FOLDERS_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		$db->db_query("update ".BAB_LDAP_DIRECTORIES_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		$db->db_query("update ".BAB_DB_DIRECTORIES_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		$db->db_query("update ".BAB_ORG_CHARTS_TBL." set id_dgowner='0' where id_dgowner='".$id."'");	
		}
	}


?>