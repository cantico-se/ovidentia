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
 * Get a list of available calendar ID
 * @return array
 */
function bab_getAvailableCalendars()
{
	$return = array();

	$tmp =  array_merge(getAvailableUsersCalendars(),getAvailableGroupsCalendars(),getAvailableResourcesCalendars());
	foreach ($tmp as $arr) {
		$return[] = $arr['idcal'];
	}

	return $return;
}




function getAvailableUsersCalendars($bwrite = false)
{
	global $babBody, $BAB_SESS_USERID,$BAB_SESS_USER;
	bab_getICalendars()->initializeCalendars();

	$tab = array();

	if( bab_getICalendars()->id_percal != 0 )
		{
		$tab[] = array('idcal' => bab_getICalendars()->id_percal, 'name' => $GLOBALS['BAB_SESS_USER']);
		}

	if( count(bab_getICalendars()->usercal) > 0 )
		{
		reset(bab_getICalendars()->usercal);
		while( $row=each(bab_getICalendars()->usercal) ) 
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

/**
 * 
 * @deprecated
 * 
function getAvailableGroupsCalendars($bwrite = false)
{
	global $babBody;
	bab_getICalendars()->initializeCalendars();
	$tab = array();

	if( count(bab_getICalendars()->pubcal) > 0 )
		{
		
		reset(bab_getICalendars()->pubcal);
		while( $row=each(bab_getICalendars()->pubcal) ) 
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
*/

/**
 * 
 * @deprecated
 * 
 * @param unknown_type $bwrite
 * @return unknown_type
 
function getAvailableResourcesCalendars($bwrite = false)
{
	global $babBody, $BAB_SESS_USERID,$BAB_SESS_USER;
	bab_getICalendars()->initializeCalendars();
	$tab = array();

	if( count(bab_getICalendars()->rescal) > 0 )
		{
		reset(bab_getICalendars()->rescal);
		while( $row=each(bab_getICalendars()->rescal) ) 
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
*/



//function notifyArticleDraftApprovers($id, $users)





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
	 * "type/id" reference for the default calendar or null if no default calendar accessible
	 * @var string
	 */
	private $default_calendar = null;

	

	/**
	 * 
	 * @param int $iduser		iduser can be empty for anonymous
	 * 
	 */
	public function __construct($iduser = '')
	{
		global $babBody, $babDB;

		$this->allday 		= $babBody->babsite['allday'];
		$this->usebgcolor 	= $babBody->babsite['usebgcolor'];
		$this->elapstime 	= $babBody->babsite['elapstime'];
		$this->defaultview 	= $babBody->babsite['defaultview'];
		$this->starttime 	= $babBody->babsite['start_time'];
		$this->endtime 		= $babBody->babsite['end_time'];
		$this->dispdays 	= $babBody->babsite['dispdays'];
		$this->startday 	= $babBody->babsite['startday'];
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
	 * Get personal calendar of access user from ovidentia
	 * Do not call it directly
	 * @see bab_eventCollectCalendarsBeforeDisplay::getPersonalCalendar()
	 * @return bab_PersonalCalendar
	 */
	public function getPersonalCalendar()
	{
		if( !empty($this->iduser))
		{
			global $babDB;
			
			$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$babDB->db_escape_string($this->iduser)."' and actif='Y' and type='1'");
			if( $res && $babDB->db_num_rows($res) >  0)
			{
				$arr = $babDB->db_fetch_assoc($res);
				$id_percal = $arr['id'];
				
				$data = array(
					'idcal'			=> $arr['id'],
					'idowner'		=> $this->iduser,
					'name' 			=> bab_getUserName($this->iduser), 
					'description' 	=> '',  
					'access' 		=> BAB_CAL_ACCESS_FULL
				);
				
				$backend = bab_functionality::get('CalendarBackend/Ovi');
				/*@var $backend Func_CalendarBackend_Ovi */
				
				$calendar = $backend->PersonalCalendar();
				$calendar->init($this->iduser, $data);
				
				return $calendar;
			}
		}
		
		return null;
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
		
		if(empty($this->user_calendarids) && count($this->calendars) > 0)
		{
			$keys = array_keys($this->calendars);
			$this->user_calendarids = $keys[0];
		}
			
		if (null === $this->default_calendar && $calendar->isDefaultCalendar())
		{
			$this->default_calendar = $calendar;
		}
	}
	
	
	/**
	 * return default calendar or null if no default calendar
	 * @see bab_EventCalendar::isDefaultCalendar()
	 * @return bab_EventCalendar
	 */
	public function getDefaultCalendar()
	{
		return $this->default_calendar;
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
			// init user calendars with all accessible calendars
			$keys = array_keys($this->calendars);
			
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
	
	
	
	public function getCalendars()
	{
		$this->initializeCalendars();
		
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
 * Create calendar events for CalendarBackend/Ovi
 * 
 */
class bab_cal_OviCalendarEvents
{




	/**
	 * Create a calendar period from a calendar event
	 * 
	 * @param	array	$arr
	 * 
	 * @return bab_CalendarEvent
	 */
	private function createCalendarPeriod($arr)
	{
		global $babDB;
		$event = new bab_calendarPeriod(bab_mktime($arr['start_date']), bab_mktime($arr['end_date']));
			
			
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		$editor = new bab_contentEditor('bab_calendar_event');
		$editor->setContent($arr['description']);
		$editor->setFormat($arr['description_format']);
		$arr['description']	= $editor->getHtml();
	
		$event->setProperty('UID'			, $arr['uuid']);
		$event->setProperty('DTSTART'		, $arr['start_date']);
		$event->setProperty('DTEND'			, $arr['end_date']);
		$event->setProperty('SUMMARY'		, $arr['title']);
		$event->setProperty('DESCRIPTION'	, $arr['description']);
		$event->setProperty('LOCATION'		, $arr['location']);
		$event->setProperty('CATEGORIES'	, $arr['category']);
		
		$color = isset($arr['bgcolor']) ? $arr['bgcolor'] : $arr['color'];
		$event->setColor($color);
		
		
		if ('Y' == $arr['bprivate']) {
			$event->setProperty('CLASS'	, 'PRIVATE');
		} else {
			$event->setProperty('CLASS'	, 'PUBLIC');
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
		unset($arr['uuid']);
	
		$arr['alert'] = !empty($arr['alert']);
		$arr['idcal_owners'] = array(); /* id calendars that ownes this event */
		$arr['iduser_owners'] = array();
	
	
		$resco = $babDB->db_query("
		
			SELECT o.id_cal, c.type, c.owner, o.idfai   
			FROM 
				".BAB_CAL_EVENTS_OWNERS_TBL." o, 
				".BAB_CALENDAR_TBL." c  
			WHERE 
				o.id_event ='".$babDB->db_escape_string($arr['id'])."' 
				AND c.id = o.id_cal 
			");
	
		while( $arr2 = $babDB->db_fetch_array($resco)) {
			if ($arr2['id_cal'] != $arr['id_cal']) {
				if (isset($ovi_calendars[$arr2['id_cal']])) {
					$arr['idcal_owners'][] = $ovi_calendars[$arr2['id_cal']]->getUrlIdentifier();
				} else {
					bab_debug('Error, missing calendar');
				}
			}
			
			if (BAB_CAL_USER_TYPE === (int) $arr2['type']) {
				$arr['iduser_owners'][$arr2['owner']] = $arr2['owner'];
			}
	
			if (0 !== (int) $arr2['idfai']) {
				// overright idfai because the idfai is 0 when the event is linked to others calendars
				$arr['idfai'] = (int) $arr2['idfai'];
			}
		}
	
		$arr['nbowners'] = count($arr['idcal_owners']);
		
	
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
			$event->available = true;
		}
	
		$event->setData($arr);
		
		
		return $event;
	}
	
	
	/**
	 * 
	 * @param string $where
	 * @return string
	 */
	private function getQuery($where)
	{
		global $babDB;
		
		$query = "
			SELECT 
				ceo.*, 
				ce.*,
				ca.name category,
				ca.description category_description, 
				ca.bgcolor, 
				er.id_event alert, 
				en.note 
			FROM 
				".BAB_CAL_EVENTS_OWNERS_TBL." ceo 
				LEFT JOIN ".BAB_CAL_EVENTS_TBL." ce ON ceo.id_event=ce.id 
				LEFT JOIN ".BAB_CAL_CATEGORIES_TBL." ca ON ca.id = ce.id_cat 
				LEFT JOIN ".BAB_CAL_EVENTS_REMINDERS_TBL." er ON er.id_event=ce.id AND er.id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])." 
				LEFT JOIN ".BAB_CAL_EVENTS_NOTES_TBL." en ON en.id_event=ce.id AND en.id_user=".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."
	
			WHERE 
				".$where." 
			ORDER BY 
				ce.start_date asc 
		";
		
		return $query;
	}
	
	
	
	
	
	/**
	 * set calendar events into object
	 * 
	 *  
	 * @param bab_UserPeriods				$user_periods		query result set
	 * @param array							$calendars	<bab_EventCalendar>
	 * @param object						$begin
	 * @param object						$end
	 * @param array|NULL					[$category]
	 */
	public function setEventsPeriods(bab_UserPeriods $user_periods, Array $calendars, $begin, $end, $category = NULL) {
	
		global $babDB;
		
		$backend = bab_functionality::get('CalendarBackend');
		
		$ovi_calendars = array();
		$id_calendars = array();
		$collections = array();
		
		foreach($calendars as $calendar) {
			
			if ($calendar instanceof bab_OviEventCalendar) {
				$id = $calendar->getUid();
				$id_calendars[] = $id;
				$collections[$id] = $backend->CalendarEventCollection();
				$collections[$id]->setCalendar($calendar);
			}
		}
		
		foreach(bab_getICalendars()->getCalendars() as $calendar) {
			if ($calendar instanceof bab_OviEventCalendar) {
				$ovi_calendars[$calendar->getUid()] = $calendar;
			}
		}
	
		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		
		$query_category = '';	
		if (NULL !== $category) {
			$query_category = "AND ca.name IN(".$babDB->quote($category).")";
		}
	
		
		$query = $this->getQuery(" 
			ceo.id_cal			IN(".$babDB->quote($id_calendars).") 
			AND ceo.status		!= '".BAB_CAL_STATUS_DECLINED."' 
			AND ce.start_date	<= '".$babDB->db_escape_string($end->getIsoDateTime())."' 
			AND ce.end_date		>= '".$babDB->db_escape_string($begin->getIsoDateTime())."' 
			".$query_category." 
		");
		
		$res = $babDB->db_query($query);
	
		
		while( $arr = $babDB->db_fetch_assoc($res))
		{
			$event = $this->createCalendarPeriod($arr);
			
			$collections[$arr['id_cal']]->addPeriod($event);
			$user_periods->addPeriod($event);
		}
	
	}


	/**
	 * Select one event by ical property UID
	 * 
	 * @param	string $uid
	 * @return bab_CalendarPeriod
	 */
	public function getFromUid($uid)
	{
		global $babDB;
		
		$query = $this->getQuery(" 
			ce.uuid = ".$babDB->quote($uid)." 
		");
		
		$res = $babDB->db_query($query);
		$arr = $babDB->db_fetch_assoc($res);
		
		if (!$arr)
		{
			return null;
		}
		
		return $this->createCalendarPeriod($arr);
	}
}
