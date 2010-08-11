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
include_once 'base.php';
require_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';







/**
 * Event used to collect accessibles calendars for user to display on interface
 * @package events
 */
class bab_eventCollectCalendarsBeforeDisplay extends bab_event
{

	/**
	 * 
	 * @var bab_icalendars
	 */
	private $calendar_collection;
	
	public function __construct(bab_icalendars $calendar_collection)
	{
		$this->calendar_collection = $calendar_collection;
	}
	
	
	public function addCalendar(bab_EventCalendar $calendar)
	{
		$this->calendar_collection->addCalendar($calendar);
	}
	
	/**
	 * 
	 * @return int
	 */
	public function getAccessUser()
	{
		return $this->calendar_collection->getAccessUser();
	}
	
	/**
	 * get personal calendar of access user or null if annonymous and no personal calendar
	 * @return bab_PersonalCalendar
	 */
	public function getPersonalCalendar()
	{
		return $this->calendar_collection->getPersonalCalendar();
	}
}







/**
 * Event fired when a calendar is displayed
 * use it to display periods into the calendar
 * all periods will be added to the bab_UserPeriods attached object by the registed functions in respect with the differents filters
 * @since 6.1.0
 * @package events
 */
class bab_eventBeforePeriodsCreated extends bab_event {
	
	/**
 	 * @var bab_UserPeriods
	 */
	public $periods;

	

	/**
 	 * 
	 */
	public function __construct(bab_UserPeriods $periods) {
		$this->periods = $periods;
	}
	
	
	public function getBeginDate()
	{
		return $this->periods->begin;
	}

	public function getEndDate()
	{
		return $this->periods->end;
	}
	
	public function getUsers()
	{
		return $this->periods->getUsers();
	}
}



/**
 * Event fired when a period is modified
 * @since 6.1.0
 * @package events
 */
class bab_eventPeriodModified extends bab_event {

	/**
 	 * @public
	 */
	var $begin;
	var $end;
	var $id_user;
	var $types;
	
	/**
	 * if the dates are false, the modification has no boundaries
 	 * @param 	int|false 	$begin		timestamp
	 * @param	int|false	$end		timestamp
	 * @param	int|false	$id_user
	 */
	function bab_eventPeriodModified($begin, $end, $id_user) {
		$this->begin 	= $begin;
		$this->end 		= $end;
		$this->id_user	= $id_user;
		$this->types	= BAB_PERIOD_WORKING | BAB_PERIOD_NONWORKING | BAB_PERIOD_NWDAY | BAB_PERIOD_CALEVENT | BAB_PERIOD_TSKMGR | BAB_PERIOD_VACATION;
	}

}









/**
 * Core function registered to collect events
 * @param bab_eventBeforePeriodsCreated $event
 * @return unknown_type
 */
function bab_onBeforePeriodsCreated(bab_eventBeforePeriodsCreated $event)
{
	require_once dirname(__FILE__).'/cal.ovievent.class.php';
	
	$oviEvents = new bab_cal_OviEventSelect;
	$oviEvents->processQuery($event->periods);
}


/**
 * Core function registered to collect calendars
 * add all calendars from ovidentia core to the bab_icalendars collection
 * 
 * @param bab_eventCollectCalendarsBeforeDisplay $event
 * @return unknown_type
 */
function bab_onCollectCalendarsBeforeDisplay(bab_eventCollectCalendarsBeforeDisplay $event)
{
	
	global $babDB, $babBody;
	
	$backend = bab_functionality::get('CalendarBackend/Ovi');
	/*@var $backend Func_CalendarBackend_Ovi */
	
	// public calendars
	
	$visible_public_cal = bab_getAccessibleObjects(BAB_CAL_PUB_VIEW_GROUPS_TBL, $event->getAccessUser());
	
	$res = $babDB->db_query("
		select cpt.*, ct.id as idcal
		from 
			".BAB_CAL_PUBLIC_TBL." cpt 
				left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id 
		where 
			ct.type='".BAB_CAL_PUB_TYPE."' 
			AND ct.actif='Y' 
			AND ct.id IN(".$babDB->quote($visible_public_cal).")
	");
	
	while( $arr = $babDB->db_fetch_assoc($res))
	{	
		$calendar = $backend->PublicCalendar();
		$calendar->init($event->getAccessUser(), $arr);
		$event->addCalendar($calendar);
	}
	
	
	
	// ressource calendars
	
	$visible_ressource_cal = bab_getAccessibleObjects(BAB_CAL_RES_VIEW_GROUPS_TBL, $event->getAccessUser());
	
	$res = $babDB->db_query("
		select crt.*, ct.id as idcal 
		from 
			".BAB_CAL_RESOURCES_TBL." crt 
				left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id 
		where 
			ct.type='".BAB_CAL_RES_TYPE."' 
			and ct.actif='Y' 
			AND ct.id IN(".$babDB->quote($visible_ressource_cal).")
	");
	
	while($arr = $babDB->db_fetch_assoc($res))
	{
		$calendar = $backend->RessourceCalendar();
		$calendar->init($event->getAccessUser(), $arr);
		$event->addCalendar($calendar);
	}
	
	// personal calendars
	
	$personal_calendar = $event->getPersonalCalendar();
	
	if ($personal_calendar)
	{
		$event->addCalendar($personal_calendar);

		if($babBody->babsite['iPersonalCalendarAccess'] == 'Y')
		{
			
			$query = "
				select 
					cut.*, 
					ct.owner, 
					u.firstname,
					u.lastname 
	
				from ".BAB_CALACCESS_USERS_TBL." cut 
					left join ".BAB_CALENDAR_TBL." ct on ct.id=cut.id_cal 
					left join ".BAB_USERS_TBL." u on u.id=ct.owner 
				where 
					id_user='".$babDB->db_escape_string($event->getAccessUser())."' and ct.actif='Y' and disabled='0'
			";
			$res = $babDB->db_query($query);
	
			while( $arr = $babDB->db_fetch_assoc($res))
			{
				$data = array(
				
					'idcal' 		=> $arr['id_cal'],
					'name' 			=> bab_composeUserName($arr['firstname'], $arr['lastname']),
					'description' 	=> '',
					'idowner' 		=> $arr['owner'],
					'access' 		=> $arr['bwrite']
				
				);
				
				$calendar = $backend->PersonalCalendar();
				$calendar->init($event->getAccessUser(), $data);
				$event->addCalendar($calendar);
			}
		}
	}
}