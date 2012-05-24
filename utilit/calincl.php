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
 * 
 *
 */
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



/**
 * remove type not accessible items from the calid string
 * @param $calid		ex : "type/id,type/id,type/id"
 * @return string | false
 */
function bab_isCalendarAccessValid($calid)
{
	global $babBody, $babDB;
	$ret = array();
	
	$arr = explode(',', $calid);
	$ret = array();
	foreach($arr as $refpart) {
		$calendar = bab_getICalendars()->getEventCalendar($refpart);
		if (isset($calendar)) {
			$ret[] = $refpart;
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


/**
 * Search a category by name
 * @param	string | int	$nameorid
 * @return array | null
 */
function bab_getCalendarCategory($nameorid)
{
	global $babDB;
	
	if (empty($nameorid))
	{
		return null;
	}
	
	
	$query = 'SELECT id, name, description, bgcolor FROM '.BAB_CAL_CATEGORIES_TBL." WHERE ";
	
	if (is_numeric($nameorid)) {
		$query .= "id=".$babDB->quote($nameorid);
	} else {
		$query .= "name LIKE '".$babDB->db_escape_like($nameorid)."'";
	}
	
	$res = $babDB->db_query($query);
	if (0 === $babDB->db_num_rows($res)) {
		return null;
	}
	
	return $babDB->db_fetch_assoc($res);
}






/**
 * Collection of accessible calendars for a user
 */
class bab_icalendars
{

	/**
	 * All visibles calendars indexed by end of reference type/id
	 * @var array
	 */
	private $calendars	= null;
	

	/**
	 * iduser for access tests
	 * @var int
	 */
	private $iduser 	= ''; 
	
	
	/**
	 * The default calendar to display or null if no default calendar accessible
	 * @var bab_EventCalendar
	 */
	private $default_calendar = null;

	
	/**
	 * The user personal calendar or null if no personal calendar
	 * @var bab_EventCalendar
	 */
	private $personal_calendar = null;
	
	
	/**
	 * 
	 * @var string
	 */
	public $calendar_backend;
	
	
	/**
	 * storage for personal calendar only, each user will be associated to the reference type of the calendar after initialization
	 * @var array
	 */
	private $reftype_by_user = array();
	

	/**
	 * 
	 * @param int $iduser		iduser can be empty for anonymous
	 * 
	 */
	public function __construct($iduser = '')
	{
		global $babBody, $babDB;

		$this->allday 			= $babBody->babsite['allday'];
		$this->usebgcolor 		= $babBody->babsite['usebgcolor'];
		$this->elapstime 		= $babBody->babsite['elapstime'];
		$this->defaultview 		= $babBody->babsite['defaultview'];
		$this->starttime 		= $babBody->babsite['start_time'];
		$this->endtime 			= $babBody->babsite['end_time'];
		$this->dispdays 		= $babBody->babsite['dispdays'];
		$this->startday 		= $babBody->babsite['startday'];
		$this->show_update_info = $babBody->babsite['show_update_info'];
		$this->calendar_backend = 'Ovi';
		$this->bshowonlydaysofmonth = $babBody->babsite['show_onlydays_of_month'];
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
			$res = $babDB->db_query("select * from ".BAB_CAL_USER_OPTIONS_TBL." where id_user='".$babDB->db_escape_string($this->iduser)."'");
			if( $res && $babDB->db_num_rows($res) >  0)
				{
				$arr = $babDB->db_fetch_array($res);
				$this->startday 	= $arr['startday'];
				$this->allday 		= $arr['allday'];
				$this->usebgcolor 	= $arr['usebgcolor'];
				$this->elapstime 	= $arr['elapstime'];
				$this->defaultview 	= $arr['defaultview'];
				$this->bshowonlydaysofmonth = $arr['show_onlydays_of_month'];
				$this->starttime 	= $arr['start_time'];
				$this->endtime 		= $arr['end_time'];
				if( $this->endtime == '00:00:00')
					{
					$this->endtime = '23:00:00';
					}

				if (!empty($arr['dispdays']))
					$this->dispdays = $arr['dispdays'];

				if (!empty($arr['workdays'])) 
					$this->workdays = $arr['workdays'];
				
				$this->user_calendarids = $arr['user_calendarids'];
				if ($arr['calendar_backend'])
					{
					$this->calendar_backend = $arr['calendar_backend'];
					}
				$this->show_update_info = $arr['show_update_info'];
				}
			}
		
	}
	
	/**
	 * Get user used for access rights verifications
	 * @return unknown_type
	 */
	public function getAccessUser()
	{
		return $this->iduser;
	}
	
	/**
	 * Get personal calendar of access user
	 * 
	 * @return bab_PersonalCalendar
	 */
	public function getPersonalCalendar()
	{
		$this->initializeCalendars();
		return $this->personal_calendar;
	}
	
	
	/**
	 * Add a calendar to calendar collection, do not call directly
	 * @see bab_eventCollectCalendarsBeforeDisplay::addCalendar()
	 * 
	 * @param	bab_EventCalendar	$calendar
	 * 
	 * @return unknown_type
	 */
	public function addCalendar(bab_EventCalendar $calendar)
	{
		$this->calendars[$calendar->getUrlIdentifier()] = $calendar;

			
		if (null === $this->default_calendar && $calendar->isDefaultCalendar())
		{
			$this->default_calendar = $calendar;
		}	

		if ($this->iduser && ((int) $this->iduser === (int) $calendar->getIdUser()))
		{
			$this->personal_calendar = $calendar;
		}
		
		if ($calendar instanceof bab_PersonalCalendar)
		{
			$this->reftype_by_user[$calendar->getIdUser()] = $calendar->getReferenceType();
		}
	}
	
	
	
	/**
	 * Get the reference type currently used by the user personal calendar
	 * @return string
	 */
	public function getUserReferenceType($id_user)
	{
		$this->initializeCalendars();
		if (isset($this->reftype_by_user[$id_user]))
		{
			return $this->reftype_by_user[$id_user];
		}
		
		return null;
	}
	
	
	
	/**
	 * Get a personal calendar Uid
	 * return null if no personal calendar
	 * 
	 * @param int $id_user
	 * @return int
	 */
	public function getPersonalCalendarUid($id_user)
	{
		global $babDB;
		
		$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$babDB->db_escape_string($id_user)."' and type='".BAB_CAL_USER_TYPE."'");
		if ($arr = $babDB->db_fetch_assoc($res))
		{
			return (int) $arr['id'];
		}
		
		return null;
	}
	
	
	
	
	
	
	/**
	 * return default calendar or null if no default calendar
	 * @see bab_EventCalendar::isDefaultCalendar()
	 * @return bab_EventCalendar
	 */
	public function getDefaultCalendar()
	{
		$this->initializeCalendars();
		if (null !== $this->default_calendar)
		{
			return $this->default_calendar;
		}
		
		if (null !== $this->personal_calendar)
		{
			return $this->personal_calendar;
		}
		
		foreach($this->getCalendars() as $calendar)
		{
			return $calendar;
		}
		
		return null;
	}
	


	/**
	 * Calendars of user for url
	 * @return string
	 */
	public function getUserCalendars() 
	{
		$this->initializeCalendars();
		
		
		$keys = array();
		if (!empty($this->user_calendarids)) {
			// user is logged, get recorded parameter
			$options = explode(',',$this->user_calendarids);
			
			foreach($options as $key) {
				if (isset($this->calendars[$key])) {
					$keys[] = $key;
				}
			}
			
		}
		
		if (empty($keys)) {
			// init user calendars with only my personal calendar
			
			$personal = $this->getPersonalCalendar();
			
			if (null == $personal)
			{
				$keys = array();
			} else {
			
				$keys = array($personal->getUrlIdentifier());
			}
			
		}
		
		

		return implode(',', $keys);

	}



	/**
	 * Test access to calendar function in ovidentia
	 * @return bool
	 */
	public function calendarAccess()
	{
		$this->initializeCalendars();
		
		if(count($this->calendars) > 0)
		{
			return true;
		}
		
		return false;
	}

	
	/**
	 * Initialize all accessibles calendars with an event 
	 * @return unknown_type
	 */
	public function initializeCalendars()
	{
		if (null !== $this->calendars) {
			// initialization done!
			return;
		}
		
		$this->calendars = array();
		
		require_once dirname(__FILE__).'/eventperiod.php';
		$event = new bab_eventCollectCalendarsBeforeDisplay($this);
		bab_fireEvent($event);
		// initialization done!
	}

	
	
	
	/**
	 * Get calendar infos from the type and objectid of the reference, "type/id" the format used in url for calendars
	 * @param string $reference_part
	 * @return bab_EventCalendar
	 */
	public function getEventCalendar($reference_part)
	{
		$this->initializeCalendars();
		
		if (isset($this->calendars[$reference_part])) {
			return $this->calendars[$reference_part];
		}
		
		return null;
	}
	
	
	/**
	 * Get all calendars
	 * @param int $id_dgowner	filter by delegation (optional)
	 * @return array
	 */
	public function getCalendars($id_dgowner = null)
	{
		$this->initializeCalendars();
		
		if (null !== $id_dgowner)
		{
			$id_dgowner = (int) $id_dgowner;
			
			$calendars = array();
			foreach($this->calendars as $calendar)
			{
				/*@var $calendar bab_EventCalendar */
				if ($calendar->visibleInDelegation($id_dgowner))
				{
					$calendars[$calendar->getUrlIdentifier()] = $calendar;
				}
			}
			
			return $calendars;
		}
		
		return $this->calendars;
	}
	
}






/**
 * Delete a calendar from ovidentia core by id
 * @param int $idcal
 * @return unknown_type
 */
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



/**
 * Title to display on page, the name of the calendar if there is only one calendar or a geeric title
 * @param string	$calid			calid can be a "type/id" string or a multiple reference like "type/id,type/id"
 * @return unknown_type
 */
function bab_getCalendarTitle($calid) {
	$calendar = bab_getICalendars()->getEventCalendar($calid);
	
	if (null === $calendar) {
		return bab_translate('Calendar');
	}
	
	return $calendar->getName();
}



/**
 * Get User by the email and name
 * return null if no match or multiple match
 * 
 * 
 * @param string $email
 * @param string $cn
 * 
 * @return int | null
 */
function bab_getUserIdByEmailAndName($email, $cn)
{
	global $babDB;
	$query = "select id, firstname, lastname from ".BAB_USERS_TBL." where email LIKE '".$babDB->db_escape_string($email)."'";
	$res = $babDB->db_query($query);
	
	$count = $babDB->db_num_rows($res);
	
	if(1 === $count)
	{
		$arr = $babDB->db_fetch_assoc($res);
		return (int) $arr['id'];
	}
		
		
	if (0 === $count)
	{
		return null;
	}
	
	while ($arr = $babDB->db_fetch_assoc($res))
	{
		if ($cn === bab_composeUserName($arr['firstname'], $arr['lastname']))
		{
			return (int) $arr['id'];
		}
		
		if ($cn === bab_composeUserName($arr['lastname'], $arr['firstname']))
		{
			return (int) $arr['id'];
		}
	}
	
	return null;
}


