<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
function bab_getCategoryCalName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_CATEGORIESCAL_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function bab_getResourceCalName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_RESOURCESCAL_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function bab_getCalendarId($iduser, $type)
{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_CALENDAR_TBL." where owner='$iduser' and actif='Y' and type='".$type."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['id'];
		}
	else
		{
		return 0;
		}
}

function bab_getCalendarType($idcal)
{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_CALENDAR_TBL." where id='$idcal'";
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
	$query = "select * from ".BAB_CALENDAR_TBL." where id='$idcal'";
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

function bab_getCalendarEventTitle($evtid)
{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_CAL_EVENTS_TBL." where id='$evtid'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
}

function bab_getCalendarOwnerName($idcal, $type)
{
	$ret = "";
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_CALENDAR_TBL." where id='$idcal'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['type'] == 1)
			{
			$query = "select * from ".BAB_USERS_TBL." where id='".$arr['owner']."'";
			$res = $db->db_query($query);
			$arr = $db->db_fetch_array($res);
			$ret = bab_composeUserName( $arr['firstname'], $arr['lastname']);
			}
		else if( $arr['type'] == 2)
			{
			$query = "select * from ".BAB_GROUPS_TBL." where id='".$arr['owner']."'";
			$res = $db->db_query($query);
			$arr = $db->db_fetch_array($res);
			$ret = $arr['name'];
			}
		else if( $arr['type'] == 3)
			{
			$query = "select * from ".BAB_RESOURCESCAL_TBL." where id='".$arr['owner']."'";
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
	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_CALENDAR_TBL." where id='".$calid."'"));
	switch($arr['type'])
		{
		case 1:
			if( $arr['owner'] == $GLOBALS['BAB_SESS_USERID'])
				return true;
			else
				{
				$res = $db->db_query("select * from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$calid."' and id_user='".$GLOBALS['BAB_SESS_USERID']."'");
				if( $res && $db->db_num_rows($res) > 0 )
					return true;			
				}
			break;

		case 2:
			$res = $db->db_query("select * from ".BAB_USERS_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and id_group='".$arr['owner']."'");
			if( $res && $db->db_num_rows($res) > 0 )
				return true;			
			break;
		case 3:
			$res = $db->db_query("select * from ".BAB_RESOURCESCAL_TBL." where id='".$arr['owner']."'");
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				if( $arr['id_group'] == 1 && !empty($GLOBALS['BAB_SESS_USERID']))
					return true;
				$res = $db->db_query("select * from ".BAB_USERS_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and id_group='".$arr['id_group']."'");
				if( $res && $db->db_num_rows($res) > 0 )
					return true;
				}
			break;
		}
	return false;
	}
?>