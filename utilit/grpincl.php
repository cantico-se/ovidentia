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
* @internal SEC1 PR 18/01/2007 FULL
*/

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
				$this->groupid = bab_toHtml($arr['id']);
				$this->groupname = bab_toHtml($arr['name']);
				$this->jgroupname = bab_toHtml($this->groupname, BAB_HTML_JS);
				return true;
				}
			else
				return false;

			}

		}

	$temp = new temp($cb);

	$babBody->babPopup(bab_printTemplate($temp, "groups.html", "browsegroups"));
	}

// used in add-ons from v4.0.8
function getGroupsMembers($id_grp)
	{
	if (!is_array($id_grp))
		$id_grp = array($id_grp);
	
	global $babDB;
	
	$res = $babDB->db_query("SELECT u.* FROM ".BAB_USERS_GROUPS_TBL." g, ".BAB_USERS_TBL." u WHERE g.id_group IN (".$babDB->quote($id_grp).") AND g.id_object=u.id");
	
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$i = 0;
		while ($arr = $babDB->db_fetch_array($res))
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


function bab_updateGroupInfo($id, $name, $description, $managerid, $grpdg = 0)
	{

	global $babDB;

	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");

	$babDB->db_query("UPDATE ".BAB_GROUPS_TBL." 
			SET 
				name='".$babDB->db_escape_string($name)."', 
				description = '".$babDB->db_escape_string($description)."',
				manager = '".$babDB->db_escape_string($managerid)."'
			WHERE
				id='".$babDB->db_escape_string($id)."'
			");

	return true;
	}

/**
 * Move group
 *
 * @param	int		$id
 * @param	int		$id_parent
 * @param	1|2		$moveoption 	1 only item , 2 item with all childs 
 * @param	string	$groupname
 *
 * @return boolean
 */
function bab_moveGroup($id, $id_parent, $moveoption, $groupname)
	{
	include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

	$tree =& new bab_grptree();
	$node = $tree->getNodeInfo($id);

	global $babDB;

	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");

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
		
		
	// move a group with members can add or remove access rights on the members
	bab_siteMap::clearAll();
	
		

	if ($node['id_parent'] != $id_parent)
		{
		if ($moveoption == 2)
			return $tree->moveTreeAlpha($id, $id_parent, $groupname);
		else
			return $tree->moveAlpha($id, $id_parent, $groupname);
		}

	return $tree->moveTreeAlpha($id, $id_parent, $groupname);
	}

function bab_addGroup($name, $description, $managerid, $grpdg, $parent = 1)
	{
	global $babBody, $babDB;
	
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return 0;
		}



	$req = "select * from ".BAB_GROUPS_TBL." where name='".$babDB->db_escape_string($name)."' AND id_parent='".$babDB->db_escape_string($parent)."'";	
	$res = $babDB->db_query($req);
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This group already exists");
		return 0;
		}
	else
		{
		$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
		include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

		$tree =& new bab_grptree();
		$id = $tree->addAlpha($parent, $name);
		unset($tree);

		$babDB->db_query("UPDATE ".BAB_GROUPS_TBL." 
			SET 
				name='".$babDB->db_escape_string($name)."', 
				description = '".$babDB->db_escape_string($description)."',
				manager = '".$babDB->db_escape_string($managerid)."',
				nb_set = '0', 	
				mail = 'N', 
				ustorage = 'N', 
				notes = 'N', 
				contacts = 'N', 
				directory = 'N', 
				pcalendar = 'N'
			WHERE
				id='".$babDB->db_escape_string($id)."'
			");
		
		include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
		bab_callAddonsFunction('onGroupCreate', $id);
		return $id;
		}
	}

/**
 * Checks whether $iIdGroup is the id of an existing group.
 * If $iIdParent is specified it will also check that $iIdGroup is a
 * descendant of $iIdParent.
 * 
 * @param int	$iIdGroup			The group to check for existence.
 * @param int	$iIdParent			
 * @param bool	$bStrict		If true check if the specified group is really descendant ( differnet from parent ) 			
 * @return bool
 */
function bab_isGroup($iIdGroup, $iIdParent = null, $bStrict = true)
{
	global $babDB;
	
	$aFromItem			= array();
	$aWhereClauseItem	= array();
	
	if(!is_null($iIdParent))
	{
		$aFromItem[]		= BAB_GROUPS_TBL . ' parentGrp';
		$aWhereClauseItem[] = 'parentGrp.id = ' . $babDB->quote((int)$iIdParent);
		if ($bStrict)
		{
			$aWhereClauseItem[] = 'childGrp.lf > parentGrp.lf AND childGrp.lr < parentGrp.lr';
		}
		else
		{
			$aWhereClauseItem[] = 'childGrp.lf >= parentGrp.lf AND childGrp.lr <= parentGrp.lr';
		}
	}
	
	$aFromItem[]		= BAB_GROUPS_TBL . ' childGrp';
	$aWhereClauseItem[] = 'childGrp.id = ' . $babDB->quote((int)$iIdGroup);
	
	
	$sQuery = 
		'SELECT ' .
			'childGrp.id id ' .
		'FROM ' . 
			implode(', ', $aFromItem) . ' ' . 
		'WHERE ' .  
			implode(' AND ', $aWhereClauseItem);

	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumrows = $babDB->db_num_rows($oResult);
		if(1 === $iNumrows)
		{
			return true;
		}
	}
	return false;
}

function bab_groupIsChildOf($iIdParent, $sName)
{
	global $babDB;
	
	$aFromItem			= array();
	$aWhereClauseItem	= array();
	
	$aFromItem[]		= BAB_GROUPS_TBL . ' parentGrp';
	$aFromItem[]		= BAB_GROUPS_TBL . ' childGrp';
	
	$aWhereClauseItem[] = 'parentGrp.id = ' . $babDB->quote((int)$iIdParent);
	$aWhereClauseItem[] = 'childGrp.lf > parentGrp.lf AND childGrp.lr < parentGrp.lr';
	$aWhereClauseItem[] = 'childGrp.name = \'' . $babDB->db_escape_like($sName) . '\'';
	
	$sQuery = 
		'SELECT ' .
			'childGrp.id id ' .
		'FROM ' . 
			implode(', ', $aFromItem) . ' ' . 
		'WHERE ' .  
			implode(' AND ', $aWhereClauseItem);

	//bab_debug($sQuery);
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iNumrows = $babDB->db_num_rows($oResult);
		if(1 === $iNumrows)
		{
			return $babDB->db_fetch_assoc($oResult);
		}
	}
	return false;
}


function getNextAvariableId()
{
	global $babDB;

	$res = $babDB->db_query("SELECT id FROM ".BAB_GROUPS_TBL." ORDER BY id");
	$ids = array();
	while ($arr = $babDB->db_fetch_assoc($res))
		{
		$ids[$arr['id']] = 1;
		}

	for ($i = 0; $i < BAB_ACL_GROUP_TREE ; $i ++)
		{
		if (!isset($ids[$i]))
			return $i;
		}

	die('too many groups, max : '.BAB_ACL_GROUP_TREE);
}

?>