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
	global $babBody;
	$db = $GLOBALS['babDB'];
	$ret = array();
	$res = $db->db_query("select id, type from ".BAB_CALENDAR_TBL." where id IN (".$calid.") and actif='Y'");
	while($arr = $db->db_fetch_array($res))
		{
		for( $i = 0; $i < count($babBody->calendarids); $i++ )
			{
			if( $babBody->calendarids[$i]['id'] == $arr['id'] && $babBody->calendarids[$i]['type'] == $arr['type'])
				{
				$ret[] = $arr['id'];
				}
			}
		}
	if( count($ret) > 0 )
		{
		$result = implode(',', $ret);
		}
	else
		{
		$result = false;
		}
	return $result;
	}


function getAvailableUsersCalendars($bwrite = false)
{
	global $babBody, $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();

	for( $i = 0; $i < count($babBody->calendarids); $i++ )
	{
		$add = false;
		if( $babBody->calendarids[$i]['type'] == 1 )
		{
			if( $bwrite )
			{
				if( $babBody->calendarids[$i]['access'] == 1 || $babBody->calendarids[$i]['access'] == 2 )
				{
					$add = true;
				}
			}
			else
			{
				$add = true;
			}

		if( $add )
			{
			$rr['name'] = bab_getCalendarOwnerName($babBody->calendarids[$i]['id'], 1);
			$rr['idcal'] = $babBody->calendarids[$i]['id'];
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

	for( $i = 0; $i < count($babBody->calendarids); $i++ )
	{
		$add = false;
		if( $babBody->calendarids[$i]['type'] == 2 )
		{
			if( $babBody->calendarids[$i]['owner'] == 1)
			{
				if( $bwrite && $babBody->isSuperAdmin )
				{
					$add = true;
				}
				elseif( !$bwrite )
				{
					$add = true;
				}
			}
			else
			{
				if( count($babBody->usergroups) > 0 && in_array($babBody->calendarids[$i]['owner'], $babBody->usergroups))
				{
					$add = true;
				}
			}

		if( $add )
			{
			$rr['name'] = bab_getGroupName($babBody->calendarids[$i]['owner']);
			$rr['idcal'] = $babBody->calendarids[$i]['id'];
			array_push($tab, $rr);
			}
		}
	}

	return $tab;
}


function getAvailableResourcesCalendars($bwrite = false)
{
	global $babBody, $BAB_SESS_USERID,$BAB_SESS_USER;
	$tab = array();
	$rr = array();
	if ($GLOBALS['BAB_SESS_LOGGED'])
	{
		$db = $GLOBALS['babDB'];
		for( $i = 0; $i < count($babBody->calendarids); $i++ )
		{
			if( $babBody->calendarids[$i]['type'] == 3 )
			{
				list($name) = $db->db_fetch_row($db->db_query("select name from ".BAB_RESOURCESCAL_TBL." where id='".$babBody->calendarids[$i]['owner']."'"));
				$rr['name'] = $name;
				$rr['idcal'] = $babBody->calendarids[$i]['id'];
				array_push($tab, $rr);
			}
		}
	}
	return $tab;
}

?>
