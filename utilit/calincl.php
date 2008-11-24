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

/**
* @internal SEC1 PR 20/02/2007 FULL
*/

include_once 'base.php';


/**
 * Get calendar type
 * @return int
 */
function bab_getCalendarType($idcal)
{
	global $babDB;
	$query = "select type from ".BAB_CALENDAR_TBL." where id=".$babDB->quote($idcal);
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return (int) $arr['type'];
		}
	else
		{
		return 0;
		}
}

/**
 * Get calendar owner
 * @return int
 */
function bab_getCalendarOwner($idcal)
{
	global $babDB;
	$query = "select owner from ".BAB_CALENDAR_TBL." where id=".$babDB->quote($idcal);
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return (int) $arr['owner'];
		}
	else
		{
		return 0;
		}
}


/**
 * Get calendar owner and type
 * @return array|false
 */
function bab_getCalendarOwnerAndType($idcal)
{
	global $babDB;
	$query = "select owner, type from ".BAB_CALENDAR_TBL." where id=".$babDB->quote($idcal);
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return array(
				'owner' => (int) $arr['owner'],
				'type' => (int) $arr['type']
			);
		}
	else
		{
		return false;
		}
}



function bab_getCalendarOwnerName($idcal, $type='')
{
	global $babDB;
	$ret = "";

	$res = $babDB->db_query("select type, owner from ".BAB_CALENDAR_TBL." where id=".$babDB->quote($idcal));
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['type'] == BAB_CAL_USER_TYPE)
			{
			return bab_getUserName( $arr['owner']);
			}
		else if( $arr['type'] == BAB_CAL_PUB_TYPE)
			{
			$arr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_CAL_PUBLIC_TBL." where id=".$babDB->quote($arr['owner'])));
			return $arr['name'];
			}
		else if( $arr['type'] == BAB_CAL_RES_TYPE)
			{
			$arr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_CAL_RESOURCES_TBL." where id=".$babDB->quote($arr['owner'])));
			return $arr['name'];
			}
		}

	return $ret;
}

function bab_isCalendarAccessValid($calid)
	{
	global $babBody, $babDB;
	$ret = array();
	$babBody->icalendars->initializeCalendars();

	$calid = explode(',', $calid);

	if( $babBody->icalendars->id_percal != 0 && in_array($babBody->icalendars->id_percal, $calid))
		{
		$ret[] = $babBody->icalendars->id_percal;
		}

	if( count($babBody->icalendars->usercal) > 0 )
		{
		reset($babBody->icalendars->usercal);
		while( $row=each($babBody->icalendars->usercal) ) 
			{
			if( in_array($row[0], $calid))
				{
				$ret[] = $row[0];
				}
			}
		}

	if( count($babBody->icalendars->pubcal) > 0 )
		{
		reset($babBody->icalendars->pubcal);
		while( $row=each($babBody->icalendars->pubcal) ) 
			{
			if( in_array($row[0], $calid))
				{
				$ret[] = $row[0];
				}
			}
		}
		
	if( count($babBody->icalendars->rescal) > 0 )
		{
		reset($babBody->icalendars->rescal);
		while( $row=each($babBody->icalendars->rescal) ) 
			{
			if( in_array($row[0], $calid))
				{
				$ret[] = $row[0];
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
	$babBody->icalendars->initializeCalendars();

	$tab = array();

	if( $babBody->icalendars->id_percal != 0 )
		{
		$tab[] = array('idcal' => $babBody->icalendars->id_percal, 'name' => $GLOBALS['BAB_SESS_USER']);
		}

	if( count($babBody->icalendars->usercal) > 0 )
		{
		reset($babBody->icalendars->usercal);
		while( $row=each($babBody->icalendars->usercal) ) 
			{
			if( $bwrite )
				{
				if( $row[1]['access'] == BAB_CAL_ACCESS_UPDATE || $row[1]['access'] == BAB_CAL_ACCESS_FULL || $row[1]['access'] == BAB_CAL_ACCESS_SHARED_UPDATE || $row[1]['access'] == BAB_CAL_ACCESS_SHARED_FULL)
					{
					$tab[] = array('idcal' => $row[0], 'name' => $row[1]['name']);
					}
				}
			else
				{
				$tab[] =  array('idcal' => $row[0], 'name' => $row[1]['name']);
				}
			}
		}

	return $tab;
}	


function getAvailableGroupsCalendars($bwrite = false)
{
	global $babBody;
	$babBody->icalendars->initializeCalendars();
	$tab = array();

	if( count($babBody->icalendars->pubcal) > 0 )
		{
		
		reset($babBody->icalendars->pubcal);
		while( $row=each($babBody->icalendars->pubcal) ) 
			{
			if( $bwrite )
				{
				if( $row[1]['manager'])
					{
					$tab[] = array('idcal' => $row[0], 'name' => $row[1]['name']);
					}
				}
			else
				{
				$tab[] =  array('idcal' => $row[0], 'name' => $row[1]['name']);
				}
			}
		}

	return $tab;
}


function getAvailableResourcesCalendars($bwrite = false)
{
	global $babBody, $BAB_SESS_USERID,$BAB_SESS_USER;
	$babBody->icalendars->initializeCalendars();
	$tab = array();

	if( count($babBody->icalendars->rescal) > 0 )
		{
		reset($babBody->icalendars->rescal);
		while( $row=each($babBody->icalendars->rescal) ) 
			{
			if( $bwrite )
				{
				if( $row[1]['manager'])
					{
					$tab[] = array('idcal' => $row[0], 'name' => $row[1]['name']);
					}
				}
			elseif( $row[1]['view'] || $row[1]['manager'] || $row[1]['add'])
				{
				$tab[] =  array('idcal' => $row[0], 'name' => $row[1]['name']);
				}
			}
		}

	return $tab;
}

//function notifyArticleDraftApprovers($id, $users)


class bab_icalendars
{
	var $id_percal = 0; // personal calendar
	var $usercal = array(); // other users personal calendars
	var $pubcal = array(); // public calendars
	var $rescal = array(); // resources calendars
	var $busercal = false; // personnal calendar
	var $bpubcal = false; // public calendar
	var $brescal = false; // resource calendar
	var $iduser = ''; // resource calendar

	function bab_icalendars($iduser = '')
	{
		global $babBody, $babDB;

		$pcalendar = false;
		$this->allday = $babBody->babsite['allday'];
		$this->usebgcolor = $babBody->babsite['usebgcolor'];
		$this->elapstime = $babBody->babsite['elapstime'];
		$this->defaultview = $babBody->babsite['defaultview'];
		$this->starttime = $babBody->babsite['start_time'];
		$this->endtime = $babBody->babsite['end_time'];
		$this->dispdays = $babBody->babsite['dispdays'];
		$this->startday = $babBody->babsite['startday'];
		$this->user_calendarids = '';
		if( empty($iduser) && isset($GLOBALS['BAB_SESS_USERID']))
			{
			$this->iduser = $GLOBALS['BAB_SESS_USERID'];
			}
		else
			{
			$this->iduser = $iduser;
			}


		if( !empty($this->iduser))
			{
			if( !empty($GLOBALS['BAB_SESS_USERID']) )
				{
				foreach($babBody->usergroups as $idg)
					{
					if( isset($babBody->ovgroups[$idg]['pcalendar']) && $babBody->ovgroups[$idg]['pcalendar'] == 'Y')
						{
						$pcalendar = true;
						}
					}
				}


			if( $pcalendar )
				{
				$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$babDB->db_escape_string($this->iduser)."' and actif='Y' and type='1'");
				if( $res && $babDB->db_num_rows($res) >  0)
					{
					$arr = $babDB->db_fetch_array($res);
					$this->id_percal = $arr['id'];
					}		
				}

			$res = $babDB->db_query("select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($this->iduser)."'");
			if( $res && $babDB->db_num_rows($res) >  0)
				{
				$arr = $babDB->db_fetch_array($res);
				$this->startday = $arr['startday'];
				$this->allday = $arr['allday'];
				$this->usebgcolor = $arr['usebgcolor'];
				$this->elapstime = $arr['elapstime'];
				$this->defaultview = $arr['defaultview'];
				$this->starttime = $arr['start_time'];
				$this->endtime = $arr['end_time'];
				if( $this->endtime == '00:00:00')
					{
					$this->endtime = '23:00:00';
					}

				if (!empty($arr['dispdays']))
					$this->dispdays = $arr['dispdays'];

				if (!empty($arr['workdays'])) 
					$this->workdays = $arr['workdays'];
				
				$this->user_calendarids = $arr['user_calendarids'];
				}
			}
		
		if( empty($this->user_calendarids) && $this->id_percal != 0)
			{
			$this->user_calendarids = $this->id_percal;
			}
	}


	function getUserCalendars() 
	{

		if (!empty($this->user_calendarids)) {
			// user is logged
			return $this->user_calendarids;

		}
		else {
			// not logged, init user calendars with all accessible calendars
			$arr = getAvailableGroupsCalendars();
			$arr += getAvailableResourcesCalendars();
			
			$tmp = array();
			foreach($arr as $cal) {
				$tmp[] = $cal['idcal'];
			}

			$this->user_calendarids = implode(',', $tmp);
		}

		return $this->user_calendarids;

	}



	function initializePublicCalendars()
	{
		global $babDB;
		$this->bpubcal = true;

		$res = $babDB->db_query("select cpt.*, ct.id as idcal, ct.owner from ".BAB_CAL_PUBLIC_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.type='".BAB_CAL_PUB_TYPE."' and ct.actif='Y'");
		while( $arr = $babDB->db_fetch_array($res))
			{

			if( isset($GLOBALS['BAB_SESS_USERID']))
				{
				$bgroup = bab_isAccessValid(BAB_CAL_PUB_GRP_GROUPS_TBL, $arr['idcal']);
				$bview = bab_isAccessValid(BAB_CAL_PUB_VIEW_GROUPS_TBL, $arr['idcal']);
				$bman = bab_isAccessValid(BAB_CAL_PUB_MAN_GROUPS_TBL, $arr['idcal']);
				}
			else
				{
				$bgroup = bab_isAccessValid(BAB_CAL_PUB_GRP_GROUPS_TBL, $arr['idcal'], $this->iduser);
				$bview = bab_isAccessValid(BAB_CAL_PUB_VIEW_GROUPS_TBL, $arr['idcal'], $this->iduser);
				$bman = bab_isAccessValid(BAB_CAL_PUB_MAN_GROUPS_TBL, $arr['idcal'], $this->iduser);
				}

			if ($bgroup || $bview || $bman)
				{
				$this->pubcal[$arr['idcal']]['name'] = $arr['name'];
				$this->pubcal[$arr['idcal']]['description'] = $arr['description'];
				$this->pubcal[$arr['idcal']]['type'] = BAB_CAL_PUB_TYPE;
				$this->pubcal[$arr['idcal']]['idowner'] = $arr['owner'];
				$this->pubcal[$arr['idcal']]['id_dgowner'] = $arr['id_dgowner'];
				$this->pubcal[$arr['idcal']]['idsa'] = $arr['idsa'];
				
				$this->pubcal[$arr['idcal']]['group'] = $bgroup;
				$this->pubcal[$arr['idcal']]['view'] = $bview;
				$this->pubcal[$arr['idcal']]['manager'] = $bman;
				}

			}
		if( empty($this->user_calendarids) && count($this->pubcal) > 0)
			{
			$keys = array_keys($this->pubcal);
			$this->user_calendarids = $keys[0];
			}
	}

	function initializeResourceCalendars()
	{
		global $babDB;
		$this->brescal = true;

		$res = $babDB->db_query("select crt.*, ct.id as idcal, ct.owner from ".BAB_CAL_RESOURCES_TBL." crt left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id where ct.type='".BAB_CAL_RES_TYPE."' and ct.actif='Y'");
		while( $arr = $babDB->db_fetch_array($res))
		{

			if( isset($GLOBALS['BAB_SESS_USERID']))
				{
				$bgroup = bab_isAccessValid(BAB_CAL_RES_GRP_GROUPS_TBL, $arr['idcal']);
				$bview = bab_isAccessValid(BAB_CAL_RES_VIEW_GROUPS_TBL, $arr['idcal']);
				$bman = bab_isAccessValid(BAB_CAL_RES_MAN_GROUPS_TBL, $arr['idcal']);
				$badd = bab_isAccessValid(BAB_CAL_RES_ADD_GROUPS_TBL, $arr['idcal']);
				$bupd = bab_isAccessValid(BAB_CAL_RES_UPD_GROUPS_TBL, $arr['idcal']);
				}
			else
				{
				$bgroup = bab_isAccessValid(BAB_CAL_RES_GRP_GROUPS_TBL, $arr['idcal'], $this->iduser);
				$bview = bab_isAccessValid(BAB_CAL_RES_VIEW_GROUPS_TBL, $arr['idcal'], $this->iduser);
				$bman = bab_isAccessValid(BAB_CAL_RES_MAN_GROUPS_TBL, $arr['idcal'], $this->iduser);
				$badd = bab_isAccessValid(BAB_CAL_RES_ADD_GROUPS_TBL, $arr['idcal'], $this->iduser);
				$bupd = bab_isAccessValid(BAB_CAL_RES_UPD_GROUPS_TBL, $arr['idcal'], $this->iduser);
				}

			if ($bgroup || $bview || $bman)
				{
				$this->rescal[$arr['idcal']]['name'] = $arr['name'];
				$this->rescal[$arr['idcal']]['description'] = $arr['description'];
				$this->rescal[$arr['idcal']]['type'] = BAB_CAL_RES_TYPE;
				$this->rescal[$arr['idcal']]['idowner'] = $arr['owner'];
				$this->rescal[$arr['idcal']]['id_dgowner'] = $arr['id_dgowner'];
				$this->rescal[$arr['idcal']]['idsa'] = $arr['idsa'];

				$this->rescal[$arr['idcal']]['group'] = $bgroup;
				$this->rescal[$arr['idcal']]['view'] = $bview;
				$this->rescal[$arr['idcal']]['manager'] = $bman;
				$this->rescal[$arr['idcal']]['add'] = $badd;
				$this->rescal[$arr['idcal']]['upd'] = $bupd;
				}

		}
		if( empty($this->user_calendarids) && count($this->rescal) > 0)
			{
			$keys = array_keys($this->rescal);
			$this->user_calendarids = $keys[0];
			}
	}

	function initializeUserCalendars()
	{
		global $babDB;
		$this->busercal = true;

		$res = $babDB->db_query("select cut.*, ct.owner from ".BAB_CALACCESS_USERS_TBL." cut left join ".BAB_CALENDAR_TBL." ct on ct.id=cut.id_cal left join ".BAB_USERS_TBL." u on u.id=ct.owner where id_user='".$babDB->db_escape_string($this->iduser)."' and ct.actif='Y' and disabled='0'");

		while( $arr = $babDB->db_fetch_array($res))
		{
			$this->usercal[$arr['id_cal']]['name'] = bab_getUserName($arr['owner']);
			$this->usercal[$arr['id_cal']]['description'] = '';
			$this->usercal[$arr['id_cal']]['type'] = BAB_CAL_USER_TYPE;
			$this->usercal[$arr['id_cal']]['idowner'] = $arr['owner'];
			$this->usercal[$arr['id_cal']]['access'] = $arr['bwrite'];
			$this->usercal[$arr['id_cal']]['asu_users'] = array();
			$this->usercal[$arr['id_cal']]['asf_users'] = array();

			$rs = $babDB->db_query("select cut.id_user, bwrite from ".BAB_CALACCESS_USERS_TBL." cut where id_cal='".$babDB->db_escape_string($arr['id_cal'])."'");

			while( $row =  $babDB->db_fetch_array($rs))
			{
				if( $row['bwrite'] == BAB_CAL_ACCESS_SHARED_UPDATE )
				{
					$this->usercal[$arr['id_cal']]['asu_users'][] = $row['id_user'];
				}
				elseif( $row['bwrite'] == BAB_CAL_ACCESS_SHARED_FULL )
				{
					$this->usercal[$arr['id_cal']]['asf_users'][] = $row['id_user'];
				}
			}
		}

		if( empty($this->user_calendarids) && count($this->usercal) > 0)
			{
			$keys = array_keys($this->usercal);
			$this->user_calendarids = $keys[0];
			}
	}

	function calendarAccess()
	{
		if( $this->id_percal != 0 )
		{
			return true;
		}

		if( !$this->bpubcal )
		{
			$this->initializePublicCalendars();
		}

		if( count($this->pubcal) > 0 )
		{
			return true;
		}

		if( !$this->brescal )
		{
			$this->initializeResourceCalendars();
		}

		if( count($this->rescal) > 0 )
		{
			return true;
		}

		if( !$this->busercal )
		{
			$this->initializeUserCalendars();
		}

		if( count($this->usercal) > 0 )
		{
			return true;
		}

	}

	function initializeCalendars()
	{
		if( !$this->bpubcal )
		{
			$this->initializePublicCalendars();
		}

		if( !$this->brescal )
		{
			$this->initializeResourceCalendars();
		}

		if( !$this->busercal )
		{
			$this->initializeUserCalendars();
		}
	}

	function getCalendarName($idcal)
	{
		if( $idcal == $this->id_percal )
		{
			return bab_getUserName($this->iduser);
		}
		else
		{
			$this->initializeCalendars();
			if( count($this->pubcal) > 0 )
			{
				reset($this->pubcal);
				while( $row=each($this->pubcal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['name'];
						}
					}
			}
			
			if( count($this->rescal) > 0 )
			{
				reset($this->rescal);
				while( $row=each($this->rescal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['name'];
						}
					}
			}

			if( count($this->usercal) > 0 )
			{
				reset($this->usercal);
				while( $row=each($this->usercal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['name'];
						}
					}
			}
		}
	return false;
	}



	function getCalendarType($idcal)
	{
		if( $idcal == $this->id_percal )
		{
			return BAB_CAL_USER_TYPE;
		}
		else
		{
			$this->initializeCalendars();
			if( count($this->pubcal) > 0 )
			{
				reset($this->pubcal);
				while( $row=each($this->pubcal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return BAB_CAL_PUB_TYPE;
						}
					}
			}
			
			if( count($this->rescal) > 0 )
			{
				reset($this->rescal);
				while( $row=each($this->rescal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return BAB_CAL_RES_TYPE;
						}
					}
			}

			if( count($this->usercal) > 0 )
			{
				reset($this->usercal);
				while( $row=each($this->usercal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return BAB_CAL_USER_TYPE;
						}
					}
			}
		}

	return false;
	}

	function getCalendarOwner($idcal)
	{
		if( $idcal == $this->id_percal )
		{
			return $this->iduser;
		}
		else
		{
			$this->initializeCalendars();
			if( count($this->pubcal) > 0 )
			{
				reset($this->pubcal);
				while( $row=each($this->pubcal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['idowner'];
						}
					}
			}
			
			if( count($this->rescal) > 0 )
			{
				reset($this->rescal);
				while( $row=each($this->rescal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['idowner'];
						}
					}
			}

			if( count($this->usercal) > 0 )
			{
				reset($this->usercal);
				while( $row=each($this->usercal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1]['idowner'];
						}
					}
			}
		}
	return false;
	}

	function getCalendarInfo($idcal)
	{
		if( $idcal == $this->id_percal )
		{
			return array(
				'name' => bab_getUserName($this->iduser), 
				'description' => '', 
				'type' => BAB_CAL_USER_TYPE, 
				'idowner' => $this->iduser, 
				'access' => BAB_CAL_ACCESS_FULL
				);
		}
		else
		{
			$this->initializeCalendars();
			if( count($this->pubcal) > 0 )
			{
				reset($this->pubcal);
				while( $row=each($this->pubcal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1];
						}
					}
			}
			
			if( count($this->rescal) > 0 )
			{
				reset($this->rescal);
				while( $row=each($this->rescal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1];
						}
					}
			}

			if( count($this->usercal) > 0 )
			{
				reset($this->usercal);
				while( $row=each($this->usercal) ) 
					{ 
					if( $row[0] == $idcal)
						{
						return $row[1];
						}
					}
			}
		}
	return false;
	}


}


function bab_deleteCalendar($idcal)
{
	global $babDB;

	list($type, $owner) = $babDB->db_fetch_row($babDB->db_query("select type, owner from ".BAB_CALENDAR_TBL." where id='".$babDB->db_escape_string($idcal)."'"));

	include_once $GLOBALS['babInstallPath']."admin/acl.php";

	switch( $type )
		{
		case BAB_CAL_PUB_TYPE:
			$babDB->db_query("delete from ".BAB_CAL_PUBLIC_TBL." where id='".$babDB->db_escape_string($owner)."'");
			aclDelete(BAB_CAL_PUB_MAN_GROUPS_TBL, $owner);
			aclDelete(BAB_CAL_PUB_GRP_GROUPS_TBL, $owner);
			aclDelete(BAB_CAL_PUB_VIEW_GROUPS_TBL, $owner);
			break;
		case BAB_CAL_RES_TYPE:
			$babDB->db_query("delete from ".BAB_CAL_RESOURCES_TBL." where id='".$babDB->db_escape_string($owner)."'");
			aclDelete(BAB_CAL_RES_MAN_GROUPS_TBL, $owner);
			aclDelete(BAB_CAL_RES_GRP_GROUPS_TBL, $owner);
			aclDelete(BAB_CAL_RES_VIEW_GROUPS_TBL, $owner);
			break;
		case BAB_CAL_USER_TYPE:
			$babDB->db_query("delete from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$babDB->db_escape_string($idcal)."'");	
			$babDB->db_query("delete from ".BAB_CALACCESS_USERS_TBL." where id_user='".$babDB->db_escape_string($owner)."'");	
			$babDB->db_query("delete from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($owner)."'");	
			break;
		}

	$res = $babDB->db_query("select id_event from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_cal='".$babDB->db_escape_string($idcal)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($arr['id_event'])."'");	
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event='".$babDB->db_escape_string($arr['id_event'])."'");	
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event='".$babDB->db_escape_string($arr['id_event'])."'");	
		}
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_cal='".$babDB->db_escape_string($idcal)."'");	
	$babDB->db_query("delete from ".BAB_CALENDAR_TBL." where id='".$babDB->db_escape_string($idcal)."'");	
}


function bab_getCalendarTitle($calid) {

	if ((string) $calid === (string) ((int) $calid)) {
		return $GLOBALS['babBody']->icalendars->getCalendarName($calid);
	}


	return bab_translate('Calendar');
}


/**
 * set calendar events into object
 * @see bab_userWorkingHours 
 * @param object	$obj bab_userWorkingHours instance
 * @param array		$id_calendars
 * @param object	$begin
 * @param object	$end
 * @param array|int|NULL	[$category]
 */
function bab_cal_setEventsPeriods(&$obj, $id_calendars, $begin, $end, $category = NULL) {

	global $babDB;

	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	
	$query_category = '';	
	if (NULL !== $category) {
		$query_category = "AND ce.id_cat IN(".$babDB->quote($category).")";
	}

	$events = array();
	$query = "
		SELECT 
			ceo.*, 
			ce.*,
			ca.name category,
			ca.description category_description, 
			ca.bgcolor 
		FROM 
			".BAB_CAL_EVENTS_OWNERS_TBL." ceo 
			LEFT JOIN ".BAB_CAL_EVENTS_TBL." ce ON ceo.id_event=ce.id 
			LEFT JOIN ".BAB_CAL_CATEGORIES_TBL." ca ON ca.id = ce.id_cat 

		WHERE 
			ceo.id_cal			IN(".$babDB->quote($id_calendars).") 
			AND ceo.status		!= '".BAB_CAL_STATUS_DECLINED."' 
			AND ce.start_date	<= '".$babDB->db_escape_string($end->getIsoDateTime())."' 
			AND ce.end_date		>= '".$babDB->db_escape_string($begin->getIsoDateTime())."' 

			".$query_category." 
		ORDER BY 
			ce.start_date asc 
	";

	$res = $babDB->db_query($query);

	$events = array();
	$idevtarr = array();
	
	while( $arr = $babDB->db_fetch_assoc($res))
		{
		$events[$arr['id']] = & new bab_calendarPeriod(bab_mktime($arr['start_date']), bab_mktime($arr['end_date']), BAB_PERIOD_CALEVENT);
		$xCtoPuid = & $events[$arr['id']]->getProperty('X-CTO-PUID');
		$xCtoPuid .= '.'.$arr['id'];
		
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		$editor = new bab_contentEditor('bab_calendar_event');
		$editor->setContent($arr['description']);
		$arr['description']	= $editor->getHtml();

		$events[$arr['id']]->setProperty('DTSTART'		, $arr['start_date']);
		$events[$arr['id']]->setProperty('DTEND'		, $arr['end_date']);
		$events[$arr['id']]->setProperty('SUMMARY'		, $arr['title']);
		$events[$arr['id']]->setProperty('DESCRIPTION'	, $arr['description']);
		$events[$arr['id']]->setProperty('LOCATION'		, $arr['location']);
		$events[$arr['id']]->setProperty('CATEGORIES'	, $arr['category']);
		$events[$arr['id']]->color = isset($arr['bgcolor']) ? $arr['bgcolor'] : $arr['color'];
		
		
		if ('Y' == $arr['bprivate']) {
			$events[$arr['id']]->setProperty('CLASS'	, 'PRIVATE');
		}

		unset($arr['start_date']);
		unset($arr['end_date']);
		unset($arr['title']);
		unset($arr['description']);
		unset($arr['location']);
		unset($arr['category']);
		unset($arr['bprivate']);
		unset($arr['color']);
		unset($arr['bgcolor']);

		$iarr = $GLOBALS['babBody']->icalendars->getCalendarInfo($arr['id_cal']);

		$arr['alert'] = false;
		$arr['idcal_owners'] = array(); /* id calendars that ownes this event */
		$arr['iduser_owners'] = array();
		$resco = $babDB->db_query("
		
			SELECT o.id_cal, c.type, c.owner  
			FROM 
				".BAB_CAL_EVENTS_OWNERS_TBL." o, 
				".BAB_CALENDAR_TBL." c  
			WHERE 
				o.id_event ='".$babDB->db_escape_string($arr['id'])."' 
				AND c.id = o.id_cal 
			");

		while( $arr2 = $babDB->db_fetch_array($resco)) {
			if ($arr2['id_cal'] != $arr['id_cal']) {
				$arr['idcal_owners'][] = $arr2['id_cal'];
			}
			
			if (BAB_CAL_USER_TYPE === (int) $arr2['type']) {
				$arr['iduser_owners'][$arr2['owner']] = $arr2['owner'];
			}
		}

		$arr['nbowners'] = count($arr['idcal_owners']);
		if( 
			false !== $iarr 
			&& $arr['nbowners'] == 0 
			&& $arr['id_creator'] != 0 
			&& $arr['id_creator'] != $GLOBALS['BAB_SESS_USERID'] 
			&& (isset($iarr['access']) && ($iarr['access'] == BAB_CAL_ACCESS_FULL || $iarr['access'] == BAB_CAL_ACCESS_SHARED_FULL ))
			&& ('PUBLIC' == $events[$arr['id']]->getProperty('CLASS'))
			) {
				$arr['nbowners'] = 1;
			}

		if( $arr['status'] == BAB_CAL_STATUS_NONE && $arr['idfai'] != 0 )
			{
			if( count($arrschi) > 0 && in_array($arr['idfai'], $arrschi))
				{
				$idevtarr[] = $arr['id'];
				}
			}
		else
			{
			$idevtarr[] = $arr['id'];
			}
			
		if ('Y' === $arr['bfree']) {
			$events[$arr['id']]->available = true;
		}

		$events[$arr['id']]->setData($arr);
		
		$obj->addPeriod($events[$arr['id']]);
		}

	

	if( !empty($GLOBALS['BAB_SESS_USERID']) && count($idevtarr) > 0 )
		{
		$res = $babDB->db_query("SELECT * from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event in (".$babDB->quote($idevtarr).") and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
		while( $arr = $babDB->db_fetch_array($res)) {
			
			$data = & $events[$arr['id_event']]->getData();
			if ($GLOBALS['babBody']->icalendars->id_percal == $data['id_cal']) {
				$data['note'] = $arr['note'];
			}
		}

		$res = $babDB->db_query("SELECT id_event from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event in (".$babDB->quote( $idevtarr).") and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
		while( $arr = $babDB->db_fetch_array($res)) {

			$data = & $events[$arr['id_event']]->getData();
			$data['alert'] = true;
		}
	}
}

?>