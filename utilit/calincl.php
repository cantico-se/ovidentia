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

function bab_getCalendarType($idcal)
{
	$db = $GLOBALS['babDB'];
	$query = "select type from ".BAB_CALENDAR_TBL." where id='$idcal'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['type'];
		}
	else
		{
		return 0;
		}
}

function bab_getCalendarOwner($idcal)
{
	$db = $GLOBALS['babDB'];
	$query = "select owner from ".BAB_CALENDAR_TBL." where id='$idcal'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['owner'];
		}
	else
		{
		return 0;
		}
}

function bab_getCalendarOwnerName($idcal, $type)
{
	$ret = "";
	$db = $GLOBALS['babDB'];
	$query = "select type, owner from ".BAB_CALENDAR_TBL." where id='$idcal'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['type'] == 1)
			{
			$query = "select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['owner']."'";
			$res = $db->db_query($query);
			$arr = $db->db_fetch_array($res);
			$ret = bab_composeUserName( $arr['firstname'], $arr['lastname']);
			}
		else if( $arr['type'] == 2)
			{
			$query = "select name from ".BAB_GROUPS_TBL." where id='".$arr['owner']."'";
			$res = $db->db_query($query);
			$arr = $db->db_fetch_array($res);
			$ret = $arr['name'];
			}
		else if( $arr['type'] == 3)
			{
			$query = "select name from ".BAB_RESOURCESCAL_TBL." where id='".$arr['owner']."'";
			$res = $db->db_query($query);
			$arr = $db->db_fetch_array($res);
			$ret = $arr['name'];
			}
		return $ret;
		}
	else
		{
		return $ret;
		}
}

function bab_isCalendarAccessValid($calid)
	{
	$db = $GLOBALS['babDB'];
	$arr = $db->db_fetch_array($db->db_query("select type, owner from ".BAB_CALENDAR_TBL." where id='".$calid."'"));
	switch($arr['type'])
		{
		case 1:
			if( $arr['owner'] == $GLOBALS['BAB_SESS_USERID'])
				return bab_getCalendarId($arr['owner'], $arr['type']) == 0? false: true;
			else
				{
				$res = $db->db_query("select id from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."' and id_user='".$GLOBALS['BAB_SESS_USERID']."'");
				if( $res && $db->db_num_rows($res) > 0 )
					return bab_getCalendarId($arr['owner'], $arr['type']) == 0? false: true;
				}
			break;

		case 2:
			if( $arr['owner'] == 1 && $GLOBALS['BAB_SESS_USERID'] != '')
				return true;
			$res = $db->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and id_group='".$arr['owner']."'");
			if( $res && $db->db_num_rows($res) > 0 )
				return true;			
			break;
		case 3:
			$res = $db->db_query("select id_group from ".BAB_RESOURCESCAL_TBL." where id='".$arr['owner']."'");
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				if( $arr['id_group'] == 1 && !empty($GLOBALS['BAB_SESS_USERID']))
					return true;
				$res = $db->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and id_group='".$arr['id_group']."'");
				if( $res && $db->db_num_rows($res) > 0 )
					return true;
				}
			break;
		}
	return false;
	}


function getAvailableUsersCalendars($bwrite = false)
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();

	$iducal = bab_getCalendarId($BAB_SESS_USERID, 1);
	if( $iducal != 0 )
	{
		$rr['name'] = $BAB_SESS_USER;
		$rr['idcal'] = $iducal;
		array_push($tab, $rr);
	}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_CALACCESS_USERS_TBL." where id_user='".$BAB_SESS_USERID."'");
	while($row = $db->db_fetch_array($res))
	{
		$arr = $db->db_fetch_array($db->db_query("select owner from ".BAB_CALENDAR_TBL." where actif='Y' and id='".$row['id_cal']."'"));
		$add = false;
		if( bab_getCalendarId($arr['owner'], 1) != 0)
		{
		if( $bwrite )
			{
			if($row['bwrite'] == "1" || $row['bwrite'] == "2")
				$add = true;
			}
		else
			$add = true;

		if( $add )
			{
			$rr['name'] = bab_getCalendarOwnerName($row['id_cal'], 1);
			$rr['idcal'] = $row['id_cal'];
			array_push($tab, $rr);
			}
		}
	}
	return $tab;
}	


function getAvailableGroupsCalendars($bwrite = false)
{
	global $babBody,$BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();

	$grparr = bab_getUserGroups();
	$grparr['id'][] = '1'; 
	$grparr['name'][] = ''; 

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_CALENDAR_TBL." where type='2' and actif='Y' and owner IN ( ".implode(',', $grparr['id']).")");
	while( $arr2 = $db->db_fetch_array($res))
	{
		$add = false;

		if( $bwrite )
		{
			if( $arr2['owner'] == 1 )
				{
				if( $babBody->isSuperAdmin )
					$add = true;
				}
			else
				{
				if( count($babBody->usergroups) > 0 && in_array($arr2['owner'], $babBody->usergroups))
					$add = true;
				}
		}
		else
			$add = true;

		if( $add )
		{
			if( $arr2['owner'] == 1 )
				$rr['name'] = bab_getGroupName($arr2['owner']);
			else
				$rr['name'] = $grparr['name'][bab_array_search($arr2['owner'], $grparr['id'] )];
			$rr['idcal'] = $arr2['id'];
			array_push($tab, $rr);
		}
	}

	return $tab;
}


function getAvailableResourcesCalendars($bwrite = false)
{
	global $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();

	$db = $GLOBALS['babDB'];

	$req = "select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
	$resgroups = $db->db_query($req);

	$req = "select * from ".BAB_RESOURCESCAL_TBL." where id_group='1'";
	while($arr = $db->db_fetch_array($resgroups))
	{
		$req .= " or id_group='".$arr['id']."'"; 
	}
	$res = $db->db_query($req);
	while($arr = $db->db_fetch_array($res))
	{
		$rr['name'] = $arr['name'];
		$rr['idcal'] = bab_getCalendarId($arr['id'], 3);
		array_push($tab, $rr);
	}
	return $tab;
}

?>
