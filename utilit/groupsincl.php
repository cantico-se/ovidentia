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

// this file is included in each refresh to manage groups readonly
// use grpincl.php to manipulate groups



/**
 * group access
 * manage groups cache in session
 */
class bab_Groups
{
	private static $groupPathName = array();
	
	
	/**
	 * Remove all groups data from session cache
	 * @return unknown_type
	 */
	public static function clearCache()
	{
		if (isset($_SESSION['bab_groupAccess']))
		{
			unset($_SESSION['bab_groupAccess']);
		}
	}
	
	/**
	 * Get the list of all groups
	 * cache in session
	 * 
	 * this method replace $babBody->ovgroups
	 * 
	 * @return array
	 */
	public static function getGroups()
	{
		if (!isset($_SESSION['bab_groupAccess']['ovgroups']))
		{
			self::updateUserSettings();
		}
		
		return $_SESSION['bab_groupAccess']['ovgroups'];
	}
	
	
	/**
	 * get group infos from table, throw session cache
	 * 
	 * @throws Exception 
	 * @return array
	 */
	public static function get($id_group)
	{
		if (!isset($_SESSION['bab_groupAccess']['ovgroups']))
		{
			self::updateUserSettings();
		}

		if (!isset($_SESSION['bab_groupAccess']['ovgroups'][$id_group]))
		{
			throw new Exception(sprintf('the group %s does not exists', $id_group));
		}
		
		return $_SESSION['bab_groupAccess']['ovgroups'][$id_group];
	}
	
	/**
	 * Get the list of groups where i am member of
	 * this method replace $babBody->usergroups
	 * 
	 * @return array
	 */
	public static function getUserGroups()
	{
		if (!isset($_SESSION['bab_groupAccess']['usergroups']))
		{
			self::updateUserSettings();
			
		}
		
		return $_SESSION['bab_groupAccess']['usergroups'];
	}
	
	/**
	 * 
	 * @param int $id_group
	 * @return bool
	 */
	public function inUserGroups($id_group)
	{
		$groups = self::getGroups();
		
		if (!isset($groups[$id_group]))
		{
			bab_debug(sprintf('The group does not exists : %d', $id_group));
			return false;
		}
		
		return ('Y' === $groups[$id_group]['member']);
	}
	
	/**
	 * Update session cache for user
	 * @return unknown_type
	 */
	private static function updateUserSettings()
	{
		global $babDB, $BAB_SESS_USERID;
		

		$_SESSION['bab_groupAccess']['ovgroups'] = array();
		$_SESSION['bab_groupAccess']['usergroups'] = array();
		
		$res = $babDB->db_query("select * from ".BAB_GROUPS_TBL."");
		while( $arr = $babDB->db_fetch_array($res))
			{
			$arr['member'] = 'N';
			$arr['primary'] = 'N';
			$_SESSION['bab_groupAccess']['ovgroups'][$arr['id']] = $arr;
			}

			
		$_SESSION['bab_groupAccess']['ovgroups'][BAB_ALLUSERS_GROUP]['member'] = 'Y';
		$_SESSION['bab_groupAccess']['usergroups'][] = BAB_ALLUSERS_GROUP;

		if( !empty($BAB_SESS_USERID))
		{			
			$_SESSION['bab_groupAccess']['ovgroups'][BAB_REGISTERED_GROUP]['member'] = 'Y';
			$_SESSION['bab_groupAccess']['usergroups'][] = BAB_REGISTERED_GROUP;
			
			$res=$babDB->db_query("select id_group, isprimary from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
			
			while( $arr = $babDB->db_fetch_array($res))
			{
				$_SESSION['bab_groupAccess']['usergroups'][] = $arr['id_group'];
				$_SESSION['bab_groupAccess']['ovgroups'][$arr['id_group']]['member'] = 'Y';
				$_SESSION['bab_groupAccess']['ovgroups'][$arr['id_group']]['primary'] = $arr['isprimary'];
			}
	
			$res=$babDB->db_query("select distinct id_set from ".BAB_GROUPS_SET_ASSOC_TBL." where id_group IN(".$babDB->quote($_SESSION['bab_groupAccess']['usergroups']).")");
			while( $arr = $babDB->db_fetch_array($res))
			{
				$_SESSION['bab_groupAccess']['usergroups'][] = $arr['id_set'];
				$_SESSION['bab_groupAccess']['ovgroups'][$arr['id_set']]['member'] = 'Y';
			}			
		}
		else
		{
			$_SESSION['bab_groupAccess']['ovgroups'][BAB_UNREGISTERED_GROUP]['member'] = 'Y';
			$_SESSION['bab_groupAccess']['usergroups'][] = BAB_UNREGISTERED_GROUP;
		}
		
	}
	
	
	/**
	 * 
	 * @param int $id_group
	 * @param int $id_parent
	 * @return string
	 */
	public static function getGroupPathName($id_group, $id_parent = BAB_REGISTERED_GROUP)
	{
		if (isset(self::$groupPathName[$id_parent][$id_group]))
			return self::$groupPathName[$id_parent][$id_group];
		
		include_once $GLOBALS['babInstallPath'].'utilit/grptreeincl.php';
	
		self::$groupPathName[$id_parent] = array();
		
		$tree = new bab_grptree();
		$groups = $tree->getGroups($id_parent);
		$arr = array();
		foreach ($groups as $row)
			{
			self::$groupPathName[$id_parent][$row['id']] = $row['name'];
			}
	
		return isset(self::$groupPathName[$id_parent][$id_group]) ? self::$groupPathName[$id_parent][$id_group] : '';
	}
	
	
	/**
	 * Name of group set
	 * @param int $id_group
	 * @return unknown_type
	 */
	public static function getSetOfGroupName($id_group)
	{
		static $groupset = array();
		global $babDB;
	
		if (isset($groupset[$id_group]))
			{
			return $groupset[$id_group];
			}
		
		$res = $babDB->db_query("SELECT id, name FROM ".BAB_GROUPS_TBL." WHERE nb_groups>='0'");
		while( $arr = $babDB->db_fetch_array($res))
		{
			$groupset[$arr['id']] = bab_translate("Sets of groups").' > '.$arr['name'];
		}
		return isset($groupset[$id_group]) ? $groupset[$id_group] : '';
	}
	
	
	
	
	
	/**
	 * get group name from table (raw name not translated)
	 * 
	 * @see bab_getGroupName()	Use bab_getGroupName to print group name for user
	 * 
	 * @param int	$id_group
	 * @return string
	 */
	public static function getName($id_group)
	{
		$arr = self::get($id_group);
		return $arr['name'];
	}
	
	/**
	 * return true if the group is a set of groups
	 * @return bool
	 */
	public static function isGroupSet($id_group)
	{
		$arr = self::get($id_group);
		return ($arr['nb_groups'] > 0);
	}
}

